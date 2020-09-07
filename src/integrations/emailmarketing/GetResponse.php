<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class GetResponse extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $apiKey;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'GetResponse');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your GetResponse lists to grow your audience for campaigns.');
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

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];
            $lists = $this->request('GET', 'campaigns');

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $fields = $this->request('GET', 'custom-fields');

                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                ], $this->_getCustomFields($fields));
            
                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['campaignId'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        try {
            
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
        }

        return new IntegrationFormSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');
            $name = ArrayHelper::remove($fieldValues, 'name');

            // Format custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $customFields[] = [
                            'customFieldId' => $key,
                            'value' => [$v],
                        ];
                    }
                } else {
                    $customFields[] = [
                        'customFieldId' => $key,
                        'value' => [$value],
                    ];
                }
            }

            $payload = [
                'email' => $email,
                'name' => $name,
                'campaign' => [
                    'campaignId' => $this->listId,
                ],
                'customFieldValues' => $customFields,
            ];

            $response = $this->deliverPayload($submission, 'contacts', $payload);

            if ($response === false) {
                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'accounts');
            $accountId = $response['accountId'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{accountId}” in response.', true);
                return false;
            }

        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.getresponse.com/v3/',
            'headers' => ['X-Auth-Token' => 'api-key ' . Craft::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'checkbox' => IntegrationField::TYPE_ARRAY,
            'multi_select' => IntegrationField::TYPE_ARRAY,
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                 continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['customFieldId'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['fieldType']),
            ]);
        }

        return $customFields;
    }
}