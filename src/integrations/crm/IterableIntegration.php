<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class IterableIntegration extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Iterable');
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public bool $mapToUser = false;
    public bool $mapToMessageType = false;
    public ?array $userFieldMapping = null;
    public ?array $messageTypeFieldMapping = null;
    public ?string $messageTypeId = null;


    // Public Methods
    // =========================================================================

    public function getClassHandle(): string
    {
        return 'iterable';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if (Craft::$app->getRequest()->getParam('refreshMessageTypes')) {
                // Reset the message types
                $settings['messageType'] = [];

                $response = $this->request('GET', 'messageTypes');
                $messageTypes = $response['messageTypes'] ?? [];

                $response = $this->request('GET', 'users/getFields');
                $fields = $response['fields'] ?? [];

                foreach ($messageTypes as $messageType) {
                    $messageTypeFields = array_merge([
                        new IntegrationField([
                            'handle' => 'email',
                            'name' => Craft::t('formie', 'Email'),
                            'required' => true,
                        ]),
                    ], $this->_getCustomFields($fields, [
                        'devices',
                        'email',
                        'profile',
                        'userId',
                        'emailListIds',
                        'itblDS',
                        'itblUserId',
                        'profileUpdatedAt',
                        'receivedSMSDisclaimer',
                        'subscribedMessageTypeIds',
                        'unsubscribedChannelIds',
                        'unsubscribedMessageTypeIds',
                        'userListIds',
                    ]));

                    $settings['messageTypes'][] = new IntegrationCollection([
                        'id' => (string)$messageType['id'],
                        'name' => $messageType['name'],
                        'fields' => $messageTypeFields,
                    ]);
                }

                // Sort message types by name
                usort($settings['messageTypes'], function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            } else {
                // Get User fields
                if ($this->mapToUser) {
                    $response = $this->request('GET', 'users/getFields');
                    $fields = $response['fields'] ?? [];

                    $settings['user'] = array_merge([
                        new IntegrationField([
                            'handle' => 'email',
                            'name' => Craft::t('formie', 'Email'),
                            'required' => true,
                        ]),
                    ], $this->_getCustomFields($fields, [
                        'devices',
                        'email',
                        'profile',
                        'userId',
                        'emailListIds',
                        'itblDS',
                        'itblUserId',
                        'profileUpdatedAt',
                        'receivedSMSDisclaimer',
                        'subscribedMessageTypeIds',
                        'unsubscribedChannelIds',
                        'unsubscribedMessageTypeIds',
                        'userListIds',
                    ]));
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        // Because we have split settings for partial settings fetches, enssure we populate settings from cache
        // So we need to unserialize the cached form settings, and combine with any new settings and return
        $cachedSettings = $this->cache['settings'] ?? [];

        if ($cachedSettings) {
            $formSettings = new IntegrationFormSettings();
            $formSettings->unserialize($cachedSettings);
            $settings = array_merge($formSettings->collections, $settings);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $userValues = $this->getFieldMappingValues($submission, $this->userFieldMapping, 'user');
            $messageTypeValues = $this->getFieldMappingValues($submission, $this->messageTypeFieldMapping, 'messageTypes');

            if ($this->mapToUser) {
                $email = ArrayHelper::remove($userValues, 'email');

                $userPayload = [
                    'email' => $email,
                    'preferUserId' => true,
                    'mergeNestedObjects' => true,
                ];

                if ($customFields = $this->_prepCustomFields($userValues)) {
                    $userPayload['dataFields'] = $customFields;
                }

                $response = $this->deliverPayload($submission, 'users/update', $userPayload);

                if ($response === false) {
                    return true;
                }
            }

            if ($this->mapToMessageType) {
                // Pull out email, as it needs to be top level
                $email = ArrayHelper::remove($messageTypeValues, 'email');

                $payload = [
                    'email' => $email,
                    'subscribedMessageTypeIds' => [
                        (int)$this->messageTypeId,
                    ],
                ];

                $response = $this->deliverPayload($submission, 'users/updateSubscriptions', $payload);

                if ($response === false) {
                    return true;
                }

                $code = $response['code'] ?? '';

                if ($code !== 'Success') {
                    Integration::error($this, Craft::t('formie', 'Invalid subscription status {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
                    ]), true);

                    return false;
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function fetchConnection(): bool
    {
        try {
            $this->request('GET', 'lists');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $user = $this->getFormSettingValue('user');

        // Validate the following when saving form settings
        $rules[] = [
            ['userFieldMapping'], 'validateFieldMapping', 'params' => $user, 'when' => function($model) {
                return $model->enabled && $model->mapToUser;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.iterable.com/api/',
            'headers' => ['Api_Key' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATETIME,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        // Don't use all fields, at least for the moment...
        $supportedFields = [
            'string',
            'date',
            'boolean',
        ];

        foreach ($fields as $handle => $type) {
            // Only allow supported types
            if (!in_array($type, $supportedFields)) {
                continue;
            }

            // Exclude any names
            if (in_array($handle, $excludeNames)) {
                continue;
            }

            // Exclude internal
            if (str_contains($handle, 'itbl') || str_contains($handle, 'devices')) {
                continue;
            }

            // There's no label/name returned, so create our own
            $label = StringHelper::titleize(implode(' ', StringHelper::toWords(str_replace('.', ' - ', $handle))));

            $customFields[] = new IntegrationField([
                'handle' => $handle,
                'name' => $label,
                'type' => $this->_convertFieldType($type),
                'sourceType' => $type,
            ]);
        }

        // Return alphabetical by name
        usort($customFields, function($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return $customFields;
    }

    private function _prepCustomFields(array $fields): array
    {
        return $fields;
    }
}