<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use Throwable;

use GuzzleHttp\Client;

class IterableIntegration extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Iterable';
    }


    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    #[FormIntegrationSetting]
    public bool $mapToUser = false;
    #[FormIntegrationSetting]
    public bool $mapToMessageType = false;
    #[FormIntegrationSetting]
    public ?array $userFieldMapping = null;
    #[FormIntegrationSetting]
    public ?array $messageTypeFieldMapping = null;
    #[FormIntegrationSetting]
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
    public function fetchConfig(): IntegrationConfig
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
                if ($this->mapToUser && $this->settingsContext->dataKey === 'user') {
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

        // Partial refreshes retain metadata that was not requested this time.
        $settings = array_merge($this->getConfig()->all(), $settings);

        return new IntegrationConfig($settings);
    }

    public function sendPayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);
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
                    return $this->resultForPayload(true);
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
                    return $this->resultForPayload(true);
                }

                $code = $response['code'] ?? '';

                if ($code !== 'Success') {
                    Integration::error($this, Craft::t('formie', 'Invalid subscription status {response}. Sent payload {payload}', [
                        'response' => Json::encode($response),
                        'payload' => Json::encode($payload),
                    ]), true);

                    return $this->resultForPayload(false);
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
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

        $user = $this->getConfigValue('user');

        // Validate the following when saving form settings
        $rules[] = [
            ['userFieldMapping'], 'validateFieldMapping', 'params' => $user, 'when' => function($model) {
                return $model->enabled && $model->mapToUser;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
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

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToUser',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'User']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Users']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'userFieldMapping',
            'if' => 'mapToUser',
            'dataLabel' => 'User',
            'dataKey' => 'user',
        ]);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToMessageType',
            'label' => Craft::t('formie', 'Map to Message Type'),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Message Types']),
        ]);
        $schema[] = SchemaHelper::comboboxField([
            'name' => 'messageTypeId',
            'label' => Craft::t('formie', 'Message Type'),
            'instructions' => Craft::t('formie', 'Select your {name} message type to subscribe users to.', ['name' => $this->displayName()]),
            'if' => 'mapToMessageType',
            'required' => true,
            'placeholder' => Craft::t('formie', 'Select an option'),
            'options' => $this->getCollectionOptions('messageTypes'),
        ]);

        $mappingSchema = $this->defineFieldMappingSchema('messageTypes', 'messageTypeId');
        if ($mappingSchema) {
            $schema[] = $this->getIntegrationFieldMappingField([
                'name' => 'messageTypeFieldMapping',
                'if' => 'mapToMessageType',
                'dataLabel' => 'Message Type',
                'dataKey' => 'messageTypes',
                'integrationFields' => $mappingSchema,
            ]);
        }

        return $schema;
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
