<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\EmailMarketingField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\View;

class ConstantContact extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'constantContact';


    // OAuth Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function supportsOauthConnection(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizeUrl(): string
    {
        return 'https://api.cc.email/v3/idfed';
    }

    /**
     * @inheritDoc
     */
    public function getAccessTokenUrl(): string
    {
        return 'https://idfed.constantcontact.com/as/token.oauth2';
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return $this->settings['apiKey'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return $this->settings['appSecret'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getOauthScope(): array
    {
        return ['contact_data'];
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Constant Contact');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Constant Contact lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(): bool
    {
        if ($this->enabled) {
            $apiKey = $this->settings['apiKey'] ?? '';
            $appSecret = $this->settings['appSecret'] ?? '';

            if (!$apiKey) {
                $this->addError('apiKey', Craft::t('formie', 'API key is required.'));
                return false;
            }

            if (!$appSecret) {
                $this->addError('appSecret', Craft::t('formie', 'App Secret is required.'));
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchLists()
    {
        $allLists = [];

        try {
            $response = $this->_request('GET', 'contact_lists');
            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->_request('GET', 'contact_custom_fields');

                $listFields = [
                    new EmailMarketingField([
                        'tag' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'type' => 'email',
                        'required' => true,
                    ]),
                    new EmailMarketingField([
                        'tag' => 'first_name',
                        'name' => Craft::t('formie', 'First Name'),
                        'type' => 'firstName',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'last_name',
                        'name' => Craft::t('formie', 'Last Name'),
                        'type' => 'lastName',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'job_title',
                        'name' => Craft::t('formie', 'Job Title'),
                        'type' => 'jobTitle',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'company_name',
                        'name' => Craft::t('formie', 'Company Name'),
                        'type' => 'companyName',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'phone_number',
                        'name' => Craft::t('formie', 'Phone Number'),
                        'type' => 'phoneNumber',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'anniversary',
                        'name' => Craft::t('formie', 'Anniversary'),
                        'type' => 'anniversary',
                    ]),
                ];

                $fields = $response['custom_fields'] ?? [];

                foreach ($fields as $field) {
                    $listFields[] = new EmailMarketingField([
                        'tag' => $field['custom_field_id'],
                        'name' => $field['label'],
                        'type' => $field['type'],
                    ]);
                }

                $allLists[] = new EmailMarketingList([
                    'id' => $list['list_id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));
        }

        return $allLists;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            // Deal with custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $fieldValue) {
                if (strstr($key, '-')) {
                    $customFields[] = [
                        'custom_field_id' => $key,
                        'value' => ArrayHelper::remove($fieldValues, $key),
                    ];
                }
            }

            $payload = array_merge([
                'email_address' => $email,
                'list_memberships' => [$this->listId],
                'custom_fields' => $customFields,
            ], $fieldValues);

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Add or update
            $response = $this->_request('POST', 'contacts/sign_up_form', [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            $contactId = $response['contact_id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”', [
                    'response' => Json::encode($response),
                ]));

                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.cc.email/v3/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken()->accessToken ?? '',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}