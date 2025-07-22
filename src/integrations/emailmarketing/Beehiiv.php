<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Beehiiv extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Beehiiv');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'publications');

            $lists = $response['data'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->request('GET', 'publications/' . $list['id'] . '/custom_fields');
                $fields = $response['data'] ?? [];

                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'reactivate_existing',
                        'name' => Craft::t('formie', 'Reactivate Existing'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Reactivate Existing'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'send_welcome_email',
                        'name' => Craft::t('formie', 'Send Welcome Email'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Send Welcome Email'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['id'],
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

            $payload = $this->_prepCustomFields($fieldValues);

            $response = $this->deliverPayload($submission, "publications/$this->listId/subscriptions", $payload);

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
            $response = $this->request('GET', 'publications');
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
            'base_uri' => 'https://api.beehiiv.com/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'list' => IntegrationField::TYPE_ARRAY,
            'integer' => IntegrationField::TYPE_NUMBER,
            'boolean' => IntegrationField::TYPE_BOOLEAN,
            'date' => IntegrationField::TYPE_DATETIME,
            'datetime' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['display'],
                'name' => $field['display'],
                'type' => $this->_convertFieldType($field['kind']),
                'sourceType' => $field['kind'],
            ]);
        }

        return $customFields;
    }

    private function _prepCustomFields(array $fields): array
    {
        $payload = $fields;

        foreach ($payload as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                ArrayHelper::remove($payload, $key);

                $payload['custom_fields'][] = [
                    'name' => str_replace('custom:', '', $key),
                    'value' => $value,
                ];
            }
        }

        return $payload;
    }
}