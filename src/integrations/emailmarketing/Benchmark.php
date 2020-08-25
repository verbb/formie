<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class Benchmark extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $apiKey;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Benchmark');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Benchmark lists to grow your audience for campaigns.');
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

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'Contact/');

            $lists = $response['Response']['Data'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->request('GET', "Contact/{$list['ID']}");
                $listAttributes = $response['Response']['Data'] ?? [];

                $listFields = [
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'FirstName',
                        'name' => $listAttributes['FirstnameLabel'] ?? Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'LastName',
                        'name' => $listAttributes['LastnameLabel'] ?? Craft::t('formie', 'Last Name'),
                    ]),
                ];
            
                foreach ($listAttributes as $listKey => $listAttribute) {
                    if (strstr($listKey, 'Field') && strstr($listKey, 'Name')) {
                        $listFields[] = new IntegrationField([
                            'handle' => str_replace('Name', '', $listKey),
                            'name' => $listAttribute,
                        ]);
                    }
                }

                $settings['lists'][] = new EmailMarketingList([
                    'id' => $list['ID'],
                    'name' => $list['Name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            $payload = [
                'Data' => array_merge($fieldValues, [
                    'EmailPerm' => 1,
                ]),
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Check if the email exists, API can't handle PUT or updating if it exists...
            $email = str_replace('+', '%2B', $fieldValues['Email']);

            $response = $this->request('GET', 'Contact/ContactDetails', [
                'query' => ['Search' => $email],
            ]);

            $existingContact = $response['Response']['Data'][0] ?? [];

            if ($existingContact) {
                $response = $this->request('PATCH', "Contact/{$this->listId}/ContactDetails/{$existingContact['ID']}", [
                    'json' => $payload,
                ]);

            } else {
                $response = $this->request('POST', "Contact/{$this->listId}/ContactDetails", [
                    'json' => $payload,
                ]);
            }

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            $errors = $response['Response']['Errors'] ?? [];

            if ($errors) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

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
            $response = $this->request('GET', 'Client/ProfileDetails');
            $accountId = $response['Response']['email'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{email}” in response.', true);
                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://clientapi.benchmarkemail.com/',
            'headers' => ['AuthToken' => $this->apiKey],
        ]);
    }

    /**
     * @inheritDoc
     */
    private function request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, ltrim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}