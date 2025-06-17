<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Xero as XeroProvider;

class Xero extends Crm implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return XeroProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Xero');
    }


    // Properties
    // =========================================================================

    public bool $mapToContact = false;
    public ?array $contactFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'openid',
            'email',
            'profile',
            'offline_access',
            'accounting.contacts',
            'accounting.settings',
        ];
        
        return $options;
    }

    public function afterFetchAccessToken(Token $token): void
    {
        $accessToken = $token?->getToken() ?? null;

        // Store the tenant alongside the access token data for later
        if ($accessToken) {
            $values = $token['values'];

            if ($tenants = $this->getOAuthProvider()->getTenants($accessToken)) {
                $values['tenant'] = (array)($tenants[0] ?? []);
            }

            $token['values'] = $values;
        }
    }

    public function getRequestOptions(Token $token): array
    {
        $tenantId = $token['values']['tenant']['tenantId'] ?? null;

        return [
            'headers' => [
                'Xero-Tenant-Id' => $tenantId,
            ],
        ];
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToContact) {
                $response = $this->request('GET', 'api.xro/2.0/ContactGroups');
                $groups = $response['ContactGroups'] ?? [];

                $groupOptions = [];

                foreach ($groups as $group) {
                    $groupOptions[] = [
                        'label' => $group['Name'],
                        'value' => $group['ContactGroupID'],
                    ];
                }

                $settings['contact'] = [
                    new IntegrationField([
                        'handle' => 'Name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'EmailAddress',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'FirstName',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'LastName',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'PhoneNumbers',
                        'name' => Craft::t('formie', 'Phone'),
                    ]),
                    new IntegrationField([
                        'handle' => 'CompanyNumber',
                        'name' => Craft::t('formie', 'Company Number'),
                    ]),
                    new IntegrationField([
                        'handle' => 'ContactStatus',
                        'name' => Craft::t('formie', 'Contact Status'),
                    ]),
                    new IntegrationField([
                        'handle' => 'ContactGroups',
                        'name' => Craft::t('formie', 'Contact Groups'),
                        'options' => [
                            'label' => Craft::t('formie', 'Contact Groups'),
                            'options' => $groupOptions,
                        ],
                    ]),
                ];
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

            if ($this->mapToContact) {
                $contactGroupId = ArrayHelper::remove($contactValues, 'ContactGroups');

                $contactPayload = [
                    'Contacts' => [$contactValues],
                ];

                $response = $this->deliverPayload($submission, 'api.xro/2.0/Contacts', $contactPayload);

                if ($response === false) {
                    return true;
                }

                $contactId = $response['Contacts'][0]['ContactID'];

                // Assign to group if selected
                if ($contactGroupId) {
                    $this->request('PUT', "api.xro/2.0/ContactGroups/{$contactGroupId}/Contacts", [
                        'json' => [
                            'Contacts' => [['ContactID' => $contactId]],
                        ],
                    ]);
                }
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

        $contact = $this->getFormSettingValue('contact');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }
}