<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class NoCrm extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'noCRM';
    }


    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    #[FormIntegrationSetting]
    public bool $mapToLead = false;
    #[FormIntegrationSetting]
    public ?array $leadFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        try {
            if ($this->mapToLead && $this->settingsContext->dataKey === 'lead') {
                $settings['lead'] = [
                    new IntegrationField([
                        'handle' => 'title',
                        'name' => Craft::t('formie', 'Title'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'description',
                        'name' => Craft::t('formie', 'Description'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'user_id',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'tags',
                        'name' => Craft::t('formie', 'Tags'),
                    ]),
                    new IntegrationField([
                        'handle' => 'step',
                        'name' => Craft::t('formie', 'Step'),
                    ]),
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'ping');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            if ($this->mapToLead) {
                $leadValues = $this->getFieldMappingValues($submission, $this->leadFieldMapping, 'lead');

                $payload = $leadValues;

                $response = $this->deliverPayload($submission, 'leads', $payload);

                if ($response === false) {
                    return $this->resultForPayload(true);
                }

                $leadId = $response['id'] ?? '';

                if (!$leadId) {
                    Integration::error($this, Craft::t('formie', 'Missing return “leadId” {response}. Sent payload {payload}', [
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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'apiDomain'], 'required'];

        $lead = $this->getConfigValue('lead');

        // Validate the following when saving form settings
        $rules[] = [
            ['leadFieldMapping'], 'validateFieldMapping', 'params' => $lead, 'when' => function($model) {
                return $model->enabled && $model->mapToLead;
            }, 'on' => [Integration::SCENARIO_FORM], 'skipOnEmpty' => false,
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $url = rtrim(App::parseEnv($this->apiDomain), '/');

        return Craft::createGuzzleClient([
            'base_uri' => "$url/api/v2/",
            'headers' => ['X-API-KEY' => App::parseEnv($this->apiKey)],
        ]);
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToLead',
            'label' => Craft::t('formie', 'Map to {name}', ['name' => 'Lead']),
            'instructions' => Craft::t('formie', 'Whether to map form data to {name} {label}.', ['name' => $this->displayName(), 'label' => 'Leads']),
        ]);
        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'leadFieldMapping',
            'if' => 'mapToLead',
            'dataLabel' => 'Lead',
            'dataKey' => 'lead',
        ]);

        return $schema;
    }
}
