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
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class Mailchimp extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Mailchimp');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public bool $appendTags = false;
    public bool $useDoubleOptIn = false;


    // Public Methods
    // =========================================================================

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

    public function fetchFormSettings(): IntegrationFormSettings
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

                // Fetch marketing permissions
                $response = $this->request('GET', 'lists/' . $list['id'] . '/members', [
                    'query' => [
                        'count' => 1,
                        'fields' => ['members.id', 'members.marketing_permissions'],
                    ],
                ]);

                $marketingPermissions = $response['members'][0]['marketing_permissions'] ?? [];

                foreach ($marketingPermissions as $marketingPermission) {
                    $listFields[] = new IntegrationField([
                        'handle' => 'gdpr:' . $marketingPermission['marketing_permission_id'],
                        'name' => Craft::t('formie', '(GDPR) {text}', ['text' => $marketingPermission['text']]),
                    ]);
                }

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['id'],
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

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email_address');
            $emailHash = md5(strtolower($email));

            // Pull out stuff for later
            $tags = ArrayHelper::remove($fieldValues, 'tags');
            $interestCategories = ArrayHelper::remove($fieldValues, 'interestCategories');

            $payload = [
                'email_address' => $email,
                'status' => $this->useDoubleOptIn ? 'pending' : 'subscribed',
                'status_if_new' => $this->useDoubleOptIn ? 'pending' : 'subscribed',
            ];

            // Handle marketing permissions
            foreach ($fieldValues as $key => $fieldValue) {
                if (StringHelper::startsWith($key, 'gdpr:')) {
                    $field = ArrayHelper::remove($fieldValues, $key);

                    $payload['marketing_permissions'][] = [
                        'marketing_permission_id' => str_replace('gdpr:', '', $key),
                        'enabled' => !empty($fieldValue),
                    ];
                }
            }

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
                    if ($this->appendTags) {
                        $existingTags = $this->_getExistingTags($emailHash);
                        $tags = array_merge($tags, $existingTags);
                    }

                    $payload = [
                        'tags' => array_map(function($tag) {
                            return ['name' => $tag, 'status' => 'active'];
                        }, $tags),
                    ];

                    $response = $this->deliverPayload($submission, "lists/{$this->listId}/members/{$emailHash}/tags", $payload);
                }
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
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        if ($this->_client) {
            return $this->_client;
        }

        if (!$dataCenter = $this->_getDataCenter()) {
            Integration::error($this, 'Could not find data center for Mailchimp', true);
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://' . $dataCenter . '.api.mailchimp.com/3.0/',
            'auth' => ['apikey', App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getDataCenter()
    {
        if (preg_match('/([a-zA-Z]+[\d]+)$/', App::parseEnv($this->apiKey), $matches)) {
            return $matches[1] ?? '';
        }

        return false;

    }

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields, $excludeNames = []): array
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

    /**
     * Returns a list of existing contact tags.
     *
     * @param string $emailHash
     * @return string[]
     */
    private function _getExistingTags(string $emailHash): array
    {
        $response = $this->request('GET', "lists/{$this->listId}/members/{$emailHash}/tags");

        return array_map(function($tag) {
            return $tag['name'];
        }, $response['tags']);
    }
}