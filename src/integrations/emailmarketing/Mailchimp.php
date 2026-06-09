<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;

use GuzzleHttp\Client;

use Throwable;

class Mailchimp extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Mailchimp');
    }

    protected static function defineOptionSources(): array
    {
        return [
            [
                'handle' => 'mailchimp-interests',
                'label' => Craft::t('formie', 'Interest Groups'),
                'collectionLabel' => Craft::t('formie', 'List'),
                'collectionInstructions' => Craft::t('formie', 'Choose the Mailchimp list or audience.'),
                'collectionPlaceholder' => Craft::t('formie', 'Select a list'),
                'remoteHandleLabel' => Craft::t('formie', 'Option Source'),
                'remoteHandleInstructions' => Craft::t('formie', 'Choose which Mailchimp field supplies the options.'),
                'remoteHandlePlaceholder' => Craft::t('formie', 'Select an option source'),
                'collectionRequiredMessage' => Craft::t('formie', 'Select a Mailchimp list.'),
                'remoteHandleRequiredMessage' => Craft::t('formie', 'Select a Mailchimp option source.'),
                'emptyCollectionsWarning' => Craft::t('formie', 'No Mailchimp lists are available. Refresh the integration data first.'),
                'emptySourcesWarning' => Craft::t('formie', 'No Mailchimp option sources are available. Refresh the integration data first.'),
            ],
        ];
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
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
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
                    'options' => $this->_getTagOptions((string)$list['id']),
                ]);

                // Handle any interest groups
                $response = $this->request('GET', 'lists/' . $list['id'] . '/interest-categories');

                $options = [];
                $categories = $response['categories'] ?? [];

                foreach ($categories as $category) {
                    $response = $this->request('GET', 'lists/' . $list['id'] . '/interest-categories/' . $category['id'] . '/interests');
                    $interests = $response['interests'] ?? [];

                    foreach ($interests as $interest) {
                        $categoryTitle = (string)($category['title'] ?? '');
                        $interestName = (string)($interest['name'] ?? '');

                        $options[] = [
                            'label' => $interestName,
                            'value' => $interest['id'],
                            'data' => [
                                'category' => $categoryTitle,
                            ],
                        ];
                    }
                }

                if ($options) {
                    $options = [
                        'label' => Craft::t('formie', 'Interest Categories'),
                        'options' => $options,
                    ];

                    $listFields[] = new IntegrationField([
                        'handle' => 'interestCategories',
                        'name' => Craft::t('formie', 'Interest Categories'),
                        'options' => $options,
                    ]);
                }

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
                'status_if_new' => $this->useDoubleOptIn ? 'pending' : 'subscribed',
            ];

            // Handle marketing permissions
            foreach ($fieldValues as $key => $fieldValue) {
                if (str_starts_with($key, 'gdpr:')) {
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

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        if (!$dataCenter = $this->_getDataCenter()) {
            Integration::error($this, 'Could not find data center for Mailchimp', true);
        }

        return Craft::createGuzzleClient([
            'base_uri' => 'https://' . $dataCenter . '.api.mailchimp.com/3.0/',
            'auth' => ['apikey', App::parseEnv($this->apiKey)],
        ]);
    }



    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);

        $schema[] = SchemaHelper::lightswitchField([
            'label' => Craft::t('formie', 'Append Tags'),
            'instructions' => Craft::t('formie', 'Whether to append tags to the list of existing member tags.'),
            'name' => 'appendTags',
        ]);

        $schema[] = SchemaHelper::lightswitchField([
            'label' => Craft::t('formie', 'Use Double Opt in'),
            'instructions' => Craft::t('formie', 'Whether to use double opt-in, which will send the user a confirmation email before they‘re added to the list.'),
            'name' => 'useDoubleOptIn',
        ]);

        return $schema;
    }
    

    // Private Methods
    // =========================================================================

    private function _getDataCenter(): false|string
    {
        if (preg_match('/([a-zA-Z]+[\d]+)$/', App::parseEnv($this->apiKey), $matches)) {
            return $matches[1] ?? '';
        }

        return false;

    }

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        // Don't use all fields, at least for the moment...
        // https://mailchimp.com/developer/marketing/docs/merge-fields/
        $supportedFields = [
            'text',
            'number',
            'radio',
            'dropdown',
            'date',
            // 'birthday',
            // 'address',
            'zip',
            'phone',
            'url',
            // 'imageurl',
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
                'sourceType' => $field['type'],
                'required' => $field['required'],
            ]);
        }

        return $customFields;
    }

    private function _getExistingTags(string $emailHash): array
    {
        $response = $this->request('GET', "lists/{$this->listId}/members/{$emailHash}/tags");

        return array_map(function($tag) {
            return $tag['name'];
        }, $response['tags']);
    }

    private function _getTagOptions(string $listId): array
    {
        $response = $this->request('GET', "lists/{$listId}/tag-search", [
            'query' => [
                'count' => 1000,
            ],
        ]);

        $tags = $response['tags'] ?? [];

        if (!$tags) {
            return [];
        }

        return [
            'label' => Craft::t('formie', 'Tags'),
            'options' => array_values(array_filter(array_map(function($tag) {
                $name = trim((string)($tag['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'label' => $name,
                    // Mailchimp member tag updates expect tag names, not tag IDs.
                    'value' => $name,
                ];
            }, $tags))),
        ];
    }
}