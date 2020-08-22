<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
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

class ActiveCampaign extends Crm
{
    // Properties
    // =========================================================================

    public $handle = 'activeCampaignCrm';
    public $mapToContact = false;
    public $mapToDeal = false;
    public $mapToOrganisation = false;
    public $contactFieldMapping;
    public $dealFieldMapping;
    public $organisationFieldMapping;



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
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your ActiveCampaign lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/crm/dist/img/active-campaign.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/active-campaign/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/active-campaign/_form-settings", [
            'integration' => $this,
            'form' => $form,
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
    public function fetchFormSettings()
    {
        $settings = [
            'contact' => [
                new IntegrationField([
                    'handle' => 'mailing_list_id',
                    'name' => Craft::t('formie', 'Mailing List ID'),
                ]),
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
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
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
            ],

            'deal' => [
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                ]),
                new IntegrationField([
                    'handle' => 'currency',
                    'name' => Craft::t('formie', 'Currency'),
                ]),
                new IntegrationField([
                    'handle' => 'group',
                    'name' => Craft::t('formie', 'Group'),
                ]),
                new IntegrationField([
                    'handle' => 'owner',
                    'name' => Craft::t('formie', 'Owner'),
                ]),
                new IntegrationField([
                    'handle' => 'percent',
                    'name' => Craft::t('formie', 'Percent'),
                ]),
                new IntegrationField([
                    'handle' => 'stage',
                    'name' => Craft::t('formie', 'Stage'),
                ]),
                new IntegrationField([
                    'handle' => 'status',
                    'name' => Craft::t('formie', 'Status'),
                ]),
            ],

            'organisation' => [
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                ]),
            ],
        ];

        try {
            // $response = $this->_request('GET', 'fields');
            // $fields = $response['fields'] ?? [];

            // // Don't use all fields, at least for the moment...
            // $supportedFields = [
            //     'text',
            //     'textarea',
            //     'hidden',
            //     'dropdown',
            //     'radio',
            //     'date',
            //     // 'checkbox',
            //     // 'listbox',
            // ];

            // foreach ($fields as $field) {
            //     if (in_array($field['type'], $supportedFields)) {
            //         $settings['contact'][] = new IntegrationField([
            //             'handle' => $field['id'],
            //             'name' => $field['title'],
            //             'type' => $field['type'],
            //         ]);
            //     }
            // }

            // // Fetch deal-specific fields
            // $response = $this->_request('GET', 'dealCustomFieldMeta');
            // $fields = $response['dealCustomFieldMeta'] ?? [];

            // foreach ($fields as $field) {
            //     if (in_array($field['type'], $supportedFields)) {
            //         $settings['deal'][] = new IntegrationField([
            //             'handle' => $field['id'],
            //             'name' => $field['title'],
            //             'type' => $field['type'],
            //         ]);
            //     }
            // }
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
            $response = $this->_request('GET', '');
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