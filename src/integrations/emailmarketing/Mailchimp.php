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

class Mailchimp extends EmailMarketing
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $useDoubleOptIn = false;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Mailchimp');
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
            $response = $this->request('GET', 'lists', [
                'query' => [
                    'fields' => 'lists.id,lists.name',
                    'count' => 1000,
                ],
            ]);

            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->request('GET', 'lists/' . $list['id'] . '/merge-fields', [
                    'query' => [
                        'count' => 1000,
                    ],
                ]);

                $listFields = [new IntegrationField([
                    'handle' => 'email_address',
                    'name' => Craft::t('formie', 'Email'),
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
                        $listFields[] = new IntegrationField([
                            'handle' => $field['tag'],
                            'name' => $field['name'],
                            'type' => $field['type'],
                            'required' => $field['required'],
                        ]);
                    }
                }

                $settings['lists'][] = new EmailMarketingList([
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

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email_address');
            $emailHash = md5(strtolower($email));

            $payload = [
                'email_address' => $email,
                'status' => (bool)$this->useDoubleOptIn ? 'pending' : 'subscribed',
                'merge_fields' => $fieldValues,
            ];

            $response = $this->deliverPayload($submission, "lists/{$this->listId}/members/$emailHash", $payload, 'PUT');

            if ($response === false) {
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
            $response = $this->request('GET', '/');
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


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        if (!$dataCenter = $this->_getDataCenter()) {
            Integration::error($this, 'Could not find data center for Mailchimp', true);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://' . $dataCenter . '.api.mailchimp.com/3.0/',
            'auth' => ['apikey', $this->apiKey],
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getDataCenter()
    {
        if (preg_match('/([a-zA-Z]+[\d]+)$/', $this->apiKey, $matches)) {
            return $matches[1] ?? '';
        }
    }
}