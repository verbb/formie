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

class Mailcoach extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Mailcoach');
    }
    

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public ?string $apiUrl = null;


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
            $response = $this->request('GET', 'email-lists');
            $lists = $response['data'] ?? [];

            foreach ($lists as $list) {
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
                    'id' => $list['uuid'],
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

            $payload = $fieldValues;

            $response = $this->deliverPayload($submission, "email-lists/$this->listId/subscribers", $payload);

            if ($response === false) {
                return true;
            }

            $email = $response['data']['email'] ?? '';

            if (!$email) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
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
            $response = $this->request('GET', 'user');
            $email = $response['data']['email'] ?? '';

            if (!$email) {
                Integration::error($this, 'Unable to find “{email}” in response.', true);
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

        $rules[] = [['apiKey', 'apiUrl'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        $apiUrl = rtrim(App::parseEnv($this->apiUrl), '/') . '/';
        $apiKey = App::parseEnv($this->apiKey);

        return Craft::createGuzzleClient([
            'base_uri' => $apiUrl,
            'headers' => ['Authorization' => "Bearer $apiKey"],
        ]);
    }
}