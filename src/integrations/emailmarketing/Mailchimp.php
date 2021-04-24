<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

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

                $fields = $response['merge_fields'] ?? [];

                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email_address',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                ], $this->_getCustomFields($fields));

                $listFields[] = new IntegrationField([
                    'handle' => 'tags',
                    'name' => Craft::t('formie', 'Tags'),
                ]);

                // Handle any interest groups
                $response = $this->request('GET', 'lists/' . $list['id'] . '/interest-categories');

                $options = [];
                $categories = $response['categories'] ?? [];

                foreach ($categories as $category) {
                    $response = $this->request('GET', 'lists/' . $list['id'] . '/interest-categories/' . $category['id'] . '/interests');
                    $interests = $response['interests'] ?? [];

                    $opts = [];

                    foreach ($interests as $interest) {
                        $opts[] = [
                            'label' => $interest['name'],
                            'value' => $interest['id'],
                        ];
                    }

                    $options = [
                        'label' => Craft::t('formie', 'Category - {title}', ['title' => $category['title']]),
                        'options' => $opts,
                    ];
                }

                $listFields[] = new IntegrationField([
                    'handle' => 'interestCategories',
                    'name' => Craft::t('formie', 'Interest Categories'),
                    'options' => $options,
                ]);

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
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

            // Pull out stuff for later
            $tags = ArrayHelper::remove($fieldValues, 'tags');
            $interestCategories = ArrayHelper::remove($fieldValues, 'interestCategories');

            $payload = [
                'email_address' => $email,
                'status' => (bool)$this->useDoubleOptIn ? 'pending' : 'subscribed',
            ];

            if ($fieldValues) {
                $payload['merge_fields'] = $fieldValues;
            }

            // Process any interest categories.
            if ($interestCategories) {
                // Cleanup and handle multiple categories. They must have their IDs provided
                $categories = array_filter(array_map('trim', explode(',', $interestCategories)));

                if ($categories) {
                    foreach ($categories as $categoryId) {
                        $payload['interests'][$categoryId] = true;
                    }
                }
            }

            $response = $this->deliverPayload($submission, "lists/{$this->listId}/members/$emailHash", $payload, 'PUT');

            if ($response === false) {
                return true;
            }

            // Process any tags, we need to fetch them first, then add or delete them.
            if ($tags) {
                // Cleanup and handle multiple tags
                $tags = array_filter(array_map('trim', explode(',', $tags)));

                if ($tags) {
                    $payload = [
                        'tags' => array_map(function($tag) {
                            return ['name' => $tag, 'status' => 'active'];
                        }, $tags),
                    ];

                    $response = $this->deliverPayload($submission, "lists/{$this->listId}/members/{$emailHash}/tags", $payload);
                }
            }
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

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
            Integration::apiError($this, $e);

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
            'auth' => ['apikey', Craft::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getDataCenter()
    {
        if (preg_match('/([a-zA-Z]+[\d]+)$/', Craft::parseEnv($this->apiKey), $matches)) {
            return $matches[1] ?? '';
        }
    }

    /**
     * @inheritDoc
     */
    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'number' => IntegrationField::TYPE_NUMBER,
            'phone' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    private function _getCustomFields($fields, $excludeNames = [])
    {
        $customFields = [];

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

        foreach ($fields as $key => $field) {
            // // Only allow supported types
            if (!in_array($field['type'], $supportedFields)) {
                 continue;
            }

            // Exclude any names
            if (in_array($field['name'], $excludeNames)) {
                 continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['tag'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
                'required' => $field['required'],
            ]);
        }

        return $customFields;
    }
}