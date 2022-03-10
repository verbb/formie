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
use craft\helpers\Json;

use Throwable;

class Sender extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Sender');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Sender lists to grow your audience for campaigns.');
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

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $lists = $this->_request([
                'method' => 'listGetAllLists',
                'params' => [
                    'api_key' => App::parseEnv($this->apiKey),
                ],
            ]);

            foreach ($lists as $list) {
                $listFields = [
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'firstname',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'lastname',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                ];

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['title'],
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

            $payload = [
                'method' => 'listSubscribe',
                'params' => [
                    'api_key' => App::parseEnv($this->apiKey),
                    'list_id' => $this->listId,
                    'emails' => $fieldValues,
                ],
            ];

            // Because we pass via reference, we need variables
            $endpoint = 'listSubscribe';
            $method = 'POST';

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
                return true;
            }

            // Add or update
            $response = $this->_request($payload);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, 'listSubscribe', $payload, 'POST', $response)) {
                return true;
            }

            $contactId = $response['success'] ?? '';

            if (!$contactId) {
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
            $response = $this->_request([
                'method' => 'listGetAllLists',
                'params' => [
                    'api_key' => App::parseEnv($this->apiKey),
                ],
            ]);

            $accountId = $response[0]['id'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{id}” in response.', true);
                return false;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _request($data)
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query(['data' => Json::encode($data)]),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents('https://app.sender.net/api', false, $context);

        return Json::decode($result);
    }
}