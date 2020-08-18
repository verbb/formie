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

class Mailchimp extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $handle = 'mailchimp';
    public $listId;
    public $fieldMapping;
    public $useDoubleOptIn = false;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Craft::t('formie', 'Mailchimp');
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/emailmarketing/dist/img/mailchimp.svg', true);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Mailchimp lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/email-marketing/mailchimp/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/email-marketing/mailchimp/_form-settings', [
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

            if (!$apiKey) {
                $this->addError('apiKey', Craft::t('formie', 'API key is required.'));
                return false;
            }

            if (!$dataCenter = $this->_getDataCenter()) {
                $this->addError('apiKey', Craft::t('formie', 'API key may be invalid. Unable to parse data center.'));
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
                    'fields' => 'lists.id,lists.name',
                    'count' => 1000,
                ],
            ]);

            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->_request('GET', 'lists/' . $list['id'] . '/merge-fields', [
                    'query' => [
                        'count' => 1000,
                    ],
                ]);

                $listFields = [new EmailMarketingField([
                    'tag' => 'email_address',
                    'name' => Craft::t('formie', 'Email'),
                    'type' => 'email',
                    'required' => true,
                ])];

                $fields = $response['merge_fields'] ?? [];

                // Don't use all fields, at least for the moment...
                $supportedFields = [
                    'text',
                    'number',
                    // 'address',
                    'phone',
                    'date',
                    'url',
                    // 'imageurl',
                    'radio',
                    'dropdown',
                    // 'birthday',
                    'zip',
                ];

                foreach ($fields as $field) {
                    if (in_array($field['type'], $supportedFields)) {
                        $listFields[] = new EmailMarketingField([
                            'tag' => $field['tag'],
                            'name' => $field['name'],
                            'type' => $field['type'],
                            'required' => $field['required'],
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
            $email = ArrayHelper::remove($fieldValues, 'email_address');
            $emailHash = md5(strtolower($email));

            $payload = [
                'email_address' => $email,
                'status' => (bool)$this->useDoubleOptIn ? 'pending' : 'subscribed',
                'merge_fields' => $fieldValues,
            ];

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission)) {
                return false;
            }

            // Add or update
            $response = $this->_request('PUT', "lists/{$this->listId}/members/$emailHash", [
                'json' => $payload,
            ]);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, $response)) {
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
            $response = $this->_request('GET', '/');
            $error = $response['error'] ?? '';
            $accountId = $response['account_id'] ?? '';

            if ($error) {
                Integration::error($this, $error, true);
                return false;
            }

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{account_id}” in response.', true);
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

        if (!$apiKey) {
            Integration::error($this, 'Invalid API Key for Mailchimp', true);
        }

        if (!$dataCenter = $this->_getDataCenter()) {
            Integration::error($this, 'Could not find data center for Mailchimp', true);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://' . $dataCenter . '.api.mailchimp.com/3.0/',
            'auth' => ['apikey', $apiKey],
        ]);
    }

    private function _request(string $method, string $uri, array $options = [])
    {
        $response = $this->_getClient()->request($method, trim($uri, '/'), $options);

        return Json::decode((string)$response->getBody());
    }

    private function _getDataCenter()
    {
        $apiKey = $this->settings['apiKey'] ?? '';

        if (preg_match('/([a-zA-Z]+[\d]+)$/', $apiKey, $matches)) {
            return $matches[1] ?? '';
        }
    }
}