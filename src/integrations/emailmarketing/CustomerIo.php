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

use GuzzleHttp\Client;

use Throwable;

class CustomerIo extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Customer.io');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $siteId = null;
    public ?string $dataCenter = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function getApiUrl(): string
    {
        if ($this->dataCenter === 'EU') {
            return 'https://track-eu.customer.io/api/v2/';
        }

        return 'https://track.customer.io/api/v2/';
    }

    public function fetchFormSettings(): IntegrationFormSettings
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

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            $payload = [
                'type' => 'person',
                'action' => 'identify',
                'identifiers' => [
                    'email' => $email,
                ],
                'attributes' => $fieldValues,
            ];

            $response = $this->deliverPayload($submission, 'entity', $payload);

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
            $response = $this->request('POST', 'entity', [
                'json' => [
                    'type' => 'person',
                    'action' => 'identify',
                    'identifiers' => [
                        'email' => 'formie@test.com',
                    ],
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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'siteId', 'dataCenter'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => $this->getApiUrl(),
            'auth' => [App::parseEnv($this->siteId), App::parseEnv($this->apiKey)],
        ]);
    }
}