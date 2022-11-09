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
use craft\helpers\StringHelper;

class Mailjet extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $secretKey;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Mailjet');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Mailjet lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'secretKey'], 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'contactslist', [
                'query' => [
                    'IsDeleted' => false,
                    'Limit' => 1000,
                ],
            ]);
            $lists = $response['Data'] ?? [];

            $response = $this->request('GET', 'contactmetadata', [
                'query' => [
                    'Limit' => 1000,
                ]
            ]);
            $fields = $response['Data'] ?? [];

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['ID'],
                    'name' => $list['Name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);
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
            $email = ArrayHelper::remove($fieldValues, 'Email');

            $payload = [
                'Email' => $email,
                'Action' => 'addforce',
            ];

            if ($fieldValues) {
                $payload['Properties'] = $fieldValues;
            }

            $response = $this->deliverPayload($submission, "contactslist/{$this->listId}/managecontact", $payload);

            if ($response === false) {
                return true;
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

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
            $response = $this->request('GET', 'user');
            $accountId = $response['Data'][0]['ID'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{ID}” in response.', true);
                return false;
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.mailjet.com/v3/REST/',
            'auth' => [App::parseEnv($this->apiKey), App::parseEnv($this->secretKey)],
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
            'str' => IntegrationField::TYPE_STRING,
            'int' => IntegrationField::TYPE_NUMBER,
            'float' => IntegrationField::TYPE_FLOAT,
            'bool' => IntegrationField::TYPE_BOOLEAN,
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
            if (in_array($field['Name'], $excludeNames)) {
                continue;
            }

            // Any Boolean fields should have a true/false option to pick from
            $options = [];
            if ($field['Datatype'] === 'bool') {
                $options = [
                    'label' => $field['Name'],
                    'options' => [
                        [
                            'label' => Craft::t('site', 'True'),
                            'value' => true,
                        ],
                        [
                            'label' => Craft::t('site', 'False'),
                            'value' => false,
                        ],
                    ],
                ];
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['Name'],
                'name' => $field['Name'],
                'type' => $this->_convertFieldType($field['Datatype']),
                'options' => $options,
            ]);
        }

        return $customFields;
    }
}