<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class IterableIntegration extends EmailMarketing
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


    // Public Methods
    // =========================================================================

    public function getClassHandle(): string
    {
        return 'iterable';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'lists');
            $lists = $response['lists'] ?? [];

            $response = $this->request('GET', 'users/getFields');
            $fields = $response['fields'] ?? [];

            foreach ($lists as $list) {
                $listFields = array_merge([
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

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            $payload = [
                'listId' => (int)$this->listId,
                'updateExistingUsersOnly' => false,
                'subscribers' => [
                    [
                        'email' => $email,
                        'preferUserId' => true,
                        'mergeNestedObjects' => true,
                    ],
                ],
            ];

            if ($customFields = $this->_prepCustomFields($fieldValues)) {
                $payload['subscribers'][0]['dataFields'] = $customFields;
            }

            $response = $this->deliverPayload($submission, 'lists/subscribe', $payload);

            if ($response === false) {
                return true;
            }

            $successCount = $response['successCount'] ?? '';

            if (!$successCount) {
                Integration::error($this, Craft::t('formie', 'Invalid subscription status {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
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

    private function _convertFieldType($fieldType)
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
