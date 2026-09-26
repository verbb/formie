<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class Avochato extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Avochato';
    }
    

    // Properties
    // =========================================================================

    public ?string $authId = null;
    public ?string $authSecret = null;
    #[FormIntegrationSetting]
    public bool $mapToContact = false;
    #[FormIntegrationSetting]
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
            if ($this->mapToContact && $this->settingsContext->dataKey === 'contact') {
                $settings['contact'] = [
                    new IntegrationField([
                        'handle' => 'phone',
                        'name' => Craft::t('formie', 'Phone Number'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                    ]),
                    new IntegrationField([
                        'handle' => 'other_phone',
                        'name' => Craft::t('formie', 'Other Phone'),
                    ]),
                    new IntegrationField([
                        'handle' => 'company',
                        'name' => Craft::t('formie', 'Company'),
                    ]),
                    new IntegrationField([
                        'handle' => 'street',
                        'name' => Craft::t('formie', 'Street'),
                    ]),
                    new IntegrationField([
                        'handle' => 'city',
                        'name' => Craft::t('formie', 'City'),
                    ]),
                    new IntegrationField([
                        'handle' => 'state',
                        'name' => Craft::t('formie', 'State'),
                    ]),
                    new IntegrationField([
                        'handle' => 'zip',
                        'name' => Craft::t('formie', 'ZIP / Postal Code'),
                    ]),
                    new IntegrationField([
                        'handle' => 'country',
                        'name' => Craft::t('formie', 'Country'),
                    ]),
                    new IntegrationField([
                        'handle' => 'opted_out',
                        'name' => Craft::t('formie', 'Opted-Out'),
                    ]),
                    new IntegrationField([
                        'handle' => 'visible',
                        'name' => Craft::t('formie', 'Visible'),
                    ]),
                    new IntegrationField([
                        'handle' => 'tags',
                        'name' => Craft::t('formie', 'Tags'),
                    ]),
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

            $contactPayload = [
                'contacts' => [$contactValues],
            ];

            $response = $this->deliverPayload($submission, 'contacts', $contactPayload);

            if ($response === false) {
                return $this->resultForPayload(true);
            }

            $contactId = $response['data']['contact']['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($contactPayload),
                ]), true);

                return $this->resultForPayload(false);
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
            $this->request('GET', 'contacts');
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

        $rules[] = [['authId', 'authSecret'], 'required'];

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://www.avochato.com/v1/',
            'query' => [
                'auth_id' => App::parseEnv($this->authId),
                'auth_secret' => App::parseEnv($this->authSecret),
            ],
        ]);
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToContact',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Contact']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Contacts']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'contactFieldMapping',
            'if' => 'mapToContact',
            'dataLabel' => 'Contact',
            'dataKey' => 'contact',
        ]);

        return $schema;
    }


}