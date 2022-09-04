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

use GuzzleHttp\Client;

use Throwable;

class Sendinblue extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Sendinblue');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Sendinblue lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $lists = $this->_getPaginated('contacts/lists', 'lists');

            $response = $this->request('GET', 'contacts/attributes');
            $fields = $response['attributes'] ?? [];

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields));

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
                'email' => $email,
                'listIds' => [(int)$this->listId],
                'updateEnabled' => true,
            ];

            if ($fieldValues) {
                $payload['attributes'] = $fieldValues;
            }

            $response = $this->deliverPayload($submission, 'contacts', $payload);

            if ($response === false) {
                return true;
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
            $response = $this->request('GET', 'account');
            $accountId = $response['email'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{email}” in response.', true);
                return false;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.sendinblue.com/v3/',
            'headers' => ['api-key' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getPaginated($endpoint, $collection, $limit = 50, $offset = 0, $items = [])
    {
        $response = $this->request('GET', $endpoint, [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);

        $newItems = $response[$collection] ?? [];
        $total = $response['count'] ?? 0;

        $items = array_merge($items, $newItems);

        if (count($items) < $total) {
            $items = $this->_getPaginated($endpoint, $collection, $limit, $offset + $limit, $items);
        }

        return $items;
    }

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'float' => IntegrationField::TYPE_FLOAT,
            'date' => IntegrationField::TYPE_DATETIME,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                continue;
            }

            // Ignore a calculated value field
            if (isset($field['calculatedValue'])) {
                continue;
            }

            $type = $field['type'] ?? '';

            // Add in any options for some fields
            $options = [];
            $fieldOptions = $field['enumeration'] ?? [];

            foreach ($fieldOptions as $fieldOption) {
                $options[] = [
                    'label' => $fieldOption['label'],
                    'value' => $fieldOption['value'],
                ];
            }

            if ($options) {
                $options = [
                    'label' => $field['name'],
                    'options' => $options,
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($type),
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}