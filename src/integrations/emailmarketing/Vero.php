<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\attributes\Sensitive;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use Throwable;

use GuzzleHttp\Client;

class Vero extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Vero';
    }


    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;
    #[Sensitive]
    public ?string $apiSecret = null;
    #[Sensitive]
    public ?string $authToken = null;


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
            $listFields = [
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
                new IntegrationField([
                    'handle' => 'last_name',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
            ];

            $settings['lists'][] = new IntegrationCollection([
                'id' => 'account',
                'name' => Craft::t('formie', 'Account'),
                'fields' => $listFields,
            ]);
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationConfig($settings);
    }

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('POST', 'users/track', [
                'json' => [
                    'email' => 'formie@test.com',
                ],
            ]);
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
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            $payload = [
                'email' => $email,
                'data' => array_filter($fieldValues),
            ];

            $response = $this->deliverPayload($submission, 'users/track', $payload);

            if ($response === false) {
                return $this->resultForPayload(true);
            }

            $status = $response['status'] ?? '';

            if ($status !== 200) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return $this->resultForPayload(false);
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

        $rules[] = [['apiKey', 'apiSecret', 'authToken'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.getvero.com/api/v2/',
            'query' => [
                'auth_token' => App::parseEnv($this->authToken),
            ],
        ]);
    }
}
