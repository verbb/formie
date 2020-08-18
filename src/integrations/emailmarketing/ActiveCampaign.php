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

class ActiveCampaign extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'activeCampaign';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'ActiveCampaign');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/emailmarketing/dist/img/active-campaign.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your ActiveCampaign lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/email-marketing/active-campaign/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/email-marketing/active-campaign/_form-settings', [
            'integration' => $this,
            'form' => $form,
            'listOptions' => $this->getListOptions(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(): bool
    {
        if ($this->enabled) {
            $apiKey = $this->settings['apiKey'] ?? '';
            $apiUrl = $this->settings['apiUrl'] ?? '';

            if (!$apiKey) {
                $this->addError('apiKey', Craft::t('formie', 'API key is required.'));
                return false;
            }

            if (!$apiUrl) {
                $this->addError('apiUrl', Craft::t('formie', 'API URL is required.'));
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
            $response = $this->_request('GET', 'lists', [
                'query' => [
                    'limit' => 100,
                ],
            ]);

            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->_request('GET', 'fields', [
                    'query' => [
                        'limit' => 100,
                    ],
                ]);

                $fields = $response['fields'] ?? [];

                $listFields = [
                    new EmailMarketingField([
                        'tag' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'type' => 'email',
                        'required' => true,
                    ]),
                    new EmailMarketingField([
                        'tag' => 'firstName',
                        'name' => Craft::t('formie', 'First Name'),
                        'type' => 'firstName',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'lastName',
                        'name' => Craft::t('formie', 'Last Name'),
                        'type' => 'lastName',
                    ]),
                    new EmailMarketingField([
                        'tag' => 'phone',
                        'name' => Craft::t('formie', 'Phone'),
                        'type' => 'phone',
                    ]),
                ];

                // Don't use all fields, at least for the moment...
                $supportedFields = [
                    'text',
                    'textarea',
                    'hidden',
                    'dropdown',
                    'radio',
                    'date',
                    // 'checkbox',
                    // 'listbox',
                ];

                foreach ($fields as $field) {
                    if (in_array($field['type'], $supportedFields)) {
                        $listFields[] = new EmailMarketingField([
                            'tag' => $field['id'],
                            'name' => $field['title'],
                            'type' => $field['type'],
                        ]);
                    }
                }

                $allLists[] = new EmailMarketingList([
                    'id' => $list['id'],
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
            $firstName = ArrayHelper::remove($fieldValues, 'firstName');
            $lastName = ArrayHelper::remove($fieldValues, 'lastName');
            $phone = ArrayHelper::remove($fieldValues, 'phone');

            $payload = [
                'contact' => [
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                ],
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Create or update contact
            $response = $this->_request('POST', 'contact/sync', [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            $contactId = $response['contact']['id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'Missing return “contactId” {response}', [
                    'response' => Json::encode($response),
                ]));

                return false;
            }

            $payload = [
                'contactList' => [
                    'list' => $this->listId,
                    'contact' => $contactId,
                    'status' => 1,
                ],
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $payload)) {
                return false;
            }

            // Then add them to the list
            $response = $this->_request('POST', 'contactLists', [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $payload, $response)) {
                return false;
            }

            // Then finally sort out the custom fields, annoyingly, one at a time
            foreach ($fieldValues as $key => $value) {
                $payload = [
                    'fieldValue' => [
                        'contact' => $contactId,
                        'field' => $key,
                        'value' => $value,
                    ],
                ];

                // Allow events to cancel sending
                if (!$this->beforeSendPayload($submission, $payload)) {
                    return false;
                }

                $response = $this->_request('POST', 'fieldValues', [
                    'json' => $payload,
                ]);

                // Allow events to say the response is invalid
                if (!$this->afterSendPayload($submission, $payload, $response)) {
                    return false;
                }
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
            $clientId = $this->settings['clientId'] ?? '';

            $response = $this->_request('GET', 'lists');
            $error = $response['error'] ?? '';
            $lists = $response['lists'] ?? '';

            if ($error) {
                Integration::error($this, $error, true);
                return false;
            }

            if (!$lists) {
                Integration::error($this, 'Unable to find “{lists}” in response.', true);
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

    private function _getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $apiKey = $this->settings['apiKey'] ?? '';
        $apiUrl = $this->settings['apiUrl'] ?? '';

        if (!$apiKey) {
            Integration::error($this, 'Invalid API Key for Active Campaign', true);
        }

        if (!$apiUrl) {
            Integration::error($this, 'Invalid API URL for Active Campaign', true);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => trim($apiUrl, '/') . '/api/3/',
            'headers' => ['Api-Token' => $apiKey],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }
}