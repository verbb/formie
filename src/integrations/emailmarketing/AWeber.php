<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
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
use verbb\auth\providers\AWeber as AWeberProvider;

class AWeber extends EmailMarketing implements OAuthProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function supportsOAuthConnection(): bool
    {
        return true;
    }

    public static function getOAuthProviderClass(): string
    {
        return AWeberProvider::class;
    }

    public static function displayName(): string
    {
        return Craft::t('formie', 'AWeber');
    }


    // Public Methods
    // =========================================================================

    public function getAuthorizationUrlOptions(): array
    {
        $options = parent::getAuthorizationUrlOptions();

        $options['scope'] = [
            'account.read',
            'list.read',
            'list.write',
            'subscriber.read',
            'subscriber.write',
            'email.read',
            'email.write',
            'subscriber.read-extended',
            'landing-page.read',
        ];
        
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
            // Find the account first to fetch lists
            $response = $this->request('GET', 'accounts');
            $accounts = $response['entries'] ?? [];

            $listsUrl = $accounts[0]['lists_collection_link'] ?? '';
            $listsUrl = str_replace('https://api.aweber.com/1.0/', '', $listsUrl);

            $response = $this->request('GET', $listsUrl);
            $lists = $response['entries'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $response = $this->request('GET', "{$listsUrl}/{$list['id']}/custom_fields");
                $fields = $response['entries'] ?? [];

                $listFields = [
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                ];

                foreach ($fields as $field) {
                    $listFields[] = new IntegrationField([
                        'handle' => $field['name'],
                        'name' => $field['name'],
                    ]);
                }

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
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
            $email = ArrayHelper::remove($fieldValues, 'email');
            $name = ArrayHelper::remove($fieldValues, 'name');

            $payload = [
                'email' => $email,
                'name' => $name,
                'custom_fields' => $fieldValues,
                'update_existing' => true,
            ];

            // Because we pass via reference, we need variables
            $endpoint = 'accounts';
            $method = 'GET';

            // Allow events to cancel sending
            if (!$this->beforeSendPayload($submission, $endpoint, $payload, $method)) {
                return true;
            }

            // Find the account first to fetch lists
            $response = $this->request('GET', 'accounts');
            $accounts = $response['entries'] ?? [];
            $listsUrl = $accounts[0]['lists_collection_link'] ?? '';
            $listsUrl = str_replace('https://api.aweber.com/1.0/', '', $listsUrl);

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, 'accounts', $payload, 'GET', $response)) {
                return true;
            }

            if (!$listsUrl) {
                Integration::error($this, Craft::t('formie', 'API error: “{response}”. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
            }

            $response = $this->deliverPayload($submission, "{$listsUrl}/{$this->listId}/subscribers", $payload);

            if ($response === false) {
                return true;
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }
}