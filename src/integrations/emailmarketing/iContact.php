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

class iContact extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'icontact';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'iContact');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your iContact lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(): bool
    {
        if ($this->enabled) {
            $appId = $this->settings['appId'] ?? '';
            $password = $this->settings['password'] ?? '';
            $username = $this->settings['username'] ?? '';
            $accountId = $this->settings['accountId'] ?? '';
            $clientFolderId = $this->settings['clientFolderId'] ?? '';

            if (!$appId) {
                $this->addError('appId', Craft::t('formie', 'Application ID is required.'));
                return false;
            }

            if (!$password) {
                $this->addError('password', Craft::t('formie', 'Password is required.'));
                return false;
            }

            if (!$username) {
                $this->addError('username', Craft::t('formie', 'Username is required.'));
                return false;
            }

            if (!$accountId) {
                $this->addError('accountId', Craft::t('formie', 'Account ID is required.'));
                return false;
            }

            if (!$clientFolderId) {
                $this->addError('clientFolderId', Craft::t('formie', 'Client Folder ID is required.'));
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $response = $this->_request('GET', 'lists');

            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->_request('GET', 'customfields');

                $listFields = [
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'prefix',
                        'name' => Craft::t('formie', 'Prefix'),
                    ]),
                    new IntegrationField([
                        'handle' => 'firstName',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'lastName',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'suffix',
                        'name' => Craft::t('formie', 'Suffix'),
                    ]),
                    new IntegrationField([
                        'handle' => 'street',
                        'name' => Craft::t('formie', 'Street'),
                    ]),
                    new IntegrationField([
                        'handle' => 'street2',
                        'name' => Craft::t('formie', 'Street 2'),
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
                        'handle' => 'postalCode',
                        'name' => Craft::t('formie', 'Postal Code'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone',
                        'name' => Craft::t('formie', 'Phone'),
                    ]),
                    new IntegrationField([
                        'handle' => 'fax',
                        'name' => Craft::t('formie', 'Fax'),
                    ]),
                    new IntegrationField([
                        'handle' => 'business',
                        'name' => Craft::t('formie', 'Business Phone'),
                    ]),
                    new IntegrationField([
                        'handle' => 'status',
                        'name' => Craft::t('formie', 'Status'),
                    ]),
                ];

                $fields = $response['customfields'] ?? [];
            
                foreach ($fields as $field) {
                    $listFields[] = new IntegrationField([
                        'handle' => $field['customFieldId'] ,
                        'name' => $field['publicName'],
                        'type' => $field['fieldType'],
                    ]);
                }

                $settings['lists'][] = new EmailMarketingList([
                    'id' => $list['listId'],
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

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission);

            $validStatuses = ['normal', 'bounced', 'donotcontact', 'pending', 'invitable', 'deleted'];

            // Setup defaults for status
            $fieldValues['status'] = $fieldValues['status'] ?? 'normal';

            if (!in_array($fieldValues['status'], $validStatuses)) {
                $fieldValues['status'] = 'normal';
            }

            $payload = [
                'contact' => $fieldValues,
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Add or update
            $response = $this->_request('POST', 'contacts', [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            $contactId = $response['contacts'][0]['contactId'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”', [
                    'response' => Json::encode($response),
                ]));

                return false;
            }

            // Add them to the list
            $payload = [
                'subscription' => [
                    'contactId' => $contactId,
                    'listId' => $this->listId,
                    'status' => $fieldValues['status'],
                ],
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            $response = $this->_request('POST', 'subscriptions', [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            $failed = $response['failed'] ?? [];

            if ($failed) {
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

    /**
     * @inheritDoc
     */
    public function fetchConnection(): bool
    {
        try {
            $response = $this->_request('GET', 'lists');
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

    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $appId = $this->settings['appId'] ?? '';
        $username = $this->settings['username'] ?? '';
        $password = $this->settings['password'] ?? '';
        $accountId = $this->settings['accountId'] ?? '';
        $clientFolderId = $this->settings['clientFolderId'] ?? '';

        if (!$appId) {
            Integration::error($this, 'Invalid Application ID for iContact', true);
        }

        if (!$username) {
            Integration::error($this, 'Invalid Username for iContact', true);
        }

        if (!$password) {
            Integration::error($this, 'Invalid Password for iContact', true);
        }

        if (!$accountId) {
            Integration::error($this, 'Invalid Account ID for iContact', true);
        }

        if (!$clientFolderId) {
            Integration::error($this, 'Invalid Client Folder ID for iContact', true);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => "https://app.icontact.com/icp/a/{$accountId}/c/{$clientFolderId}/",
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'Api-Version' => '2.2',
                'API-AppId' => $appId,
                'API-Username' => $username,
                'API-Password' => $password,
            ],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}