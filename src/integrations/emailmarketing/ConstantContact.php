<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\ConstantContact as ConstantContactProvider;

class ConstantContact extends EmailMarketing implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return ConstantContactProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Constant Contact');
    }


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        if (array_key_exists('apiKey', $config)) {
            $config['clientId'] = ArrayHelper::remove($config, 'apiKey');
        }

        if (array_key_exists('appSecret', $config)) {
            $config['clientSecret'] = ArrayHelper::remove($config, 'appSecret');
        }

        parent::__construct($config);
    }

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        // The non-array syntax here is deliberate
        // https://community.constantcontact.com/t5/Developer-Support-ask-questions/One-or-more-scopes-are-not-configured-for-the-authorization/m-p/383293#M12904
        $options['scope'] = ['contact_data', 'offline_access'];
        
        return $options;
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', 'contact_lists');
            $lists = $response['lists'] ?? [];

            // While we're at it, fetch the fields for the list
            $response = $this->request('GET', 'contact_custom_fields');
            $fields = $response['custom_fields'] ?? [];

            foreach ($lists as $list) {
                $listFields = array_merge([
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'first_name',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'last_name',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'job_title',
                        'name' => Craft::t('formie', 'Job Title'),
                    ]),
                    new IntegrationField([
                        'handle' => 'company_name',
                        'name' => Craft::t('formie', 'Company Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'phone_number',
                        'name' => Craft::t('formie', 'Phone Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'anniversary',
                        'name' => Craft::t('formie', 'Anniversary'),
                    ]),
                ], $this->_getCustomFields($fields));

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['list_id'],
                    'name' => $list['name'],
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        // Sort alphabetically by label
        if (isset($settings['lists']) && is_array($settings['lists'])) {
            usort($settings['lists'], function($a, $b) {
                return strcmp($a->name, $b->name);
            });
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'email');

            // Deal with custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $fieldValue) {
                if (str_contains($key, '-')) {
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

            $response = $this->deliverPayload($submission, 'contacts/sign_up_form', $payload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['contact_id'] ?? '';

            if (!$contactId) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(array $fields, array $excludeNames = []): array
    {
        $customFields = [];

        foreach ($fields as $field) {
            // Exclude any names
            if (in_array($field['label'], $excludeNames)) {
                continue;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['custom_field_id'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['type']),
                'sourceType' => $field['type'],
            ]);
        }

        return $customFields;
    }
}