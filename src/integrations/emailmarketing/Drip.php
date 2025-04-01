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
use craft\helpers\Json;

use Throwable;

use verbb\auth\base\OAuthProviderInterface;
use verbb\auth\models\Token;
use verbb\auth\providers\Drip as DripProvider;

class Drip extends EmailMarketing implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return DripProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'Drip');
    }


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
            // Fetch the account first
            $response = $this->request('GET', 'accounts');
            $accountId = $response['accounts'][0]['id'] ?? '';

            $response = $this->request('GET', "{$accountId}/custom_field_identifiers");
            $fields = $response['custom_field_identifiers'] ?? [];

            $listFields = [
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
                    'handle' => 'address1',
                    'name' => Craft::t('formie', 'Address 1'),
                ]),
                new IntegrationField([
                    'handle' => 'address2',
                    'name' => Craft::t('formie', 'Address 2'),
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
                    'handle' => 'zip',
                    'name' => Craft::t('formie', 'Zip'),
                ]),
                new IntegrationField([
                    'handle' => 'country',
                    'name' => Craft::t('formie', 'Country'),
                ]),
                new IntegrationField([
                    'handle' => 'phone',
                    'name' => Craft::t('formie', 'Phone'),
                ]),
            ];

            foreach ($fields as $field) {
                $listFields[] = new IntegrationField([
                    'handle' => $field,
                    'name' => $field,
                ]);
            }

            $settings['lists'][] = new IntegrationCollection([
                'id' => 'all',
                'name' => 'All Subscribers',
                'fields' => $listFields,
            ]);
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
            $email = ArrayHelper::remove($fieldValues, 'email');
            $firstName = ArrayHelper::remove($fieldValues, 'first_name');
            $lastName = ArrayHelper::remove($fieldValues, 'last_name');
            $address1 = ArrayHelper::remove($fieldValues, 'address1');
            $address2 = ArrayHelper::remove($fieldValues, 'address2');
            $city = ArrayHelper::remove($fieldValues, 'city');
            $state = ArrayHelper::remove($fieldValues, 'state');
            $zip = ArrayHelper::remove($fieldValues, 'zip');
            $country = ArrayHelper::remove($fieldValues, 'country');
            $phone = ArrayHelper::remove($fieldValues, 'phone');

            $payload = [
                'subscribers' => [
                    [
                        // API doesn't like null values
                        'email' => $email,
                        'first_name' => $firstName ?? '',
                        'last_name' => $lastName ?? '',
                        'address1' => $address1 ?? '',
                        'address2' => $address2 ?? '',
                        'city' => $city ?? '',
                        'state' => $state ?? '',
                        'zip' => $zip ?? '',
                        'country' => $country ?? '',
                        'phone' => $phone ?? '',
                        'custom_fields' => $fieldValues,
                    ],
                ],
            ];

            // Because we pass via reference, we need variables
            $endpoint = 'accounts';
            $method = 'GET';

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
                return true;
            }

            // Fetch the account first
            $response = $this->request('GET', 'accounts');
            $accountId = $response['accounts'][0]['id'] ?? '';

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, 'accounts', $payload, 'GET', $response)) {
                return true;
            }

            $response = $this->deliverPayload($submission, "{$accountId}/subscribers", $payload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['subscribers'][0]['id'] ?? '';

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
}