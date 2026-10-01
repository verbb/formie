<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\attributes\Sensitive;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;

use Throwable;

use GuzzleHttp\Client;

class ConvertKit extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'ConvertKit';
    }

    // Properties
    // =========================================================================


    #[Sensitive]
    public ?string $apiKey = null;
    #[Sensitive]
    public ?string $apiSecret = null;

    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchConfig(): IntegrationConfig
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'forms');
            $lists = $response['forms'] ?? [];

            // While we're at it, fetch the fields for the list
            $response = $this->request('GET', 'custom_fields');
            $fields = $response['custom_fields'] ?? [];

            foreach ($lists as $list) {
                $listFields = [
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'FirstName',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                ];

                foreach ($fields as $field) {
                    $listFields[] = new IntegrationField([
                        'handle' => $field['key'],
                        'name' => $field['label'],
                    ]);
                }

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'Email');
            $firstName = ArrayHelper::remove($fieldValues, 'FirstName');

            $payload = [
                'email' => $email,
                'first_name' => $firstName,
                'fields' => $fieldValues,
            ];

            $response = $this->deliverPayload($submission, "forms/{$this->listId}/subscribe", $payload);

            if ($response === false) {
                return $this->resultForPayload(true);
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
            $response = $this->request('GET', 'account');
            $accountId = $response['primary_email_address'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{primary_email_address}” in response.', true);
                return false;
            }
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

        $rules[] = [['apiKey', 'apiSecret'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.convertkit.com/v3/',
            'query' => ['api_secret' => App::parseEnv($this->apiSecret)],
        ]);
    }

}
