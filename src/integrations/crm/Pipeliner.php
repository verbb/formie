<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Pipeliner extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Pipeliner');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiToken = null;
    public ?string $apiPassword = null;
    public ?string $apiSpaceId = null;
    public ?string $apiServiceUrl = null;
    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToContact) {
                $settings['contact'] = [
                    new IntegrationField([
                        'handle' => 'owner_id',
                        'name' => Craft::t('formie', 'Owner ID'),
                        'required' => true,
                        'options' => [
                            'label' => Craft::t('formie', 'Lists'),
                            'options' => array_map(function($client) {
                                return [
                                    'label' => $client['name'],
                                    'value' => $client['id'],
                                ];
                            }, $this->request('GET', 'entities/Clients')['data'] ?? []),
                        ],
                    ]),
                    new IntegrationField([
                        'handle' => 'address',
                        'name' => Craft::t('formie', 'Address'),
                    ]),
                    new IntegrationField([
                        'handle' => 'city',
                        'name' => Craft::t('formie', 'City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'state_province',
                        'name' => Craft::t('formie', 'State Province'),
                    ]),
                    new IntegrationField([
                        'handle' => 'zip_code',
                        'name' => Craft::t('formie', 'Zip Code'),
                    ]),
                    new IntegrationField([
                        'handle' => 'country',
                        'name' => Craft::t('formie', 'Country'),
                    ]),
                    new IntegrationField([
                        'handle' => 'comments',
                        'name' => Craft::t('formie', 'Comments'),
                    ]),
                    new IntegrationField([
                        'handle' => 'contact_type_id',
                        'name' => Craft::t('formie', 'Contact Type ID'),
                    ]),
                    new IntegrationField([
                        'handle' => 'email1',
                        'name' => Craft::t('formie', 'Email'),
                    ]),
                    new IntegrationField([
                        'handle' => 'first_name',
                        'name' => Craft::t('formie', 'First Name'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'middle_name',
                        'name' => Craft::t('formie', 'Middle Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'last_name',
                        'name' => Craft::t('formie', 'Last Name'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'phone1',
                        'name' => Craft::t('formie', 'Phone 1'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone2',
                        'name' => Craft::t('formie', 'Phone 2'),
                    ]),
                    new IntegrationField([
                        'handle' => 'position',
                        'name' => Craft::t('formie', 'Position'),
                    ]),
                    new IntegrationField([
                        'handle' => 'quick_account_name',
                        'name' => Craft::t('formie', 'Quick Account Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'title',
                        'name' => Craft::t('formie', 'Fitle'),
                    ]),
                    new IntegrationField([
                        'handle' => 'account_position',
                        'name' => Craft::t('formie', 'Account Position'),
                    ]),
                    new IntegrationField([
                        'handle' => 'is_unsubscribed',
                        'name' => Craft::t('formie', 'Is Unsubscribed'),
                    ]),
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

            $contactPayload = $contactValues;

            $response = $this->deliverPayload($submission, 'entities/Contacts', $contactPayload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['data']['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($contactPayload),
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
            $response = $this->request('GET', 'entities/Contacts');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineClient(): Client
    {
        $apiServiceUrl = App::parseEnv($this->apiServiceUrl);
        $apiSpaceId = App::parseEnv($this->apiSpaceId);

        return Craft::createGuzzleClient([
            'base_uri' => "{$apiServiceUrl}/api/v100/rest/spaces/{$apiSpaceId}/",
            'auth' => [App::parseEnv($this->apiToken), App::parseEnv($this->apiPassword)],
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiToken', 'apiPassword', 'apiSpaceId', 'apiServiceUrl'], 'required'];

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            if ($field['system_readonly']) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['name'],
                'name' => $field['translated_name'],
            ]);
        }

        return $customFields;
    }
}