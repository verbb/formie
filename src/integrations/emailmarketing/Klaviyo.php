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

use GuzzleHttp\Client;

use Throwable;

class Klaviyo extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Klaviyo');
    }

    // Properties
    // =========================================================================

    public ?string $privateApiKey = null;
    public ?string $publicApiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Klaviyo lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publicApiKey', 'privateApiKey'], 'required'];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $lists = $this->request('GET', 'v2/lists');

            foreach ($lists as $list) {
                $listFields = [
                    new IntegrationField([
                        'handle' => '$first_name',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => '$last_name',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => '$email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => '$phone_number',
                        'name' => Craft::t('formie', 'Phone Number'),
                    ]),
                    new IntegrationField([
                        'handle' => '$city',
                        'name' => Craft::t('formie', 'City'),
                    ]),
                    new IntegrationField([
                        'handle' => '$region',
                        'name' => Craft::t('formie', 'Region'),
                    ]),
                    new IntegrationField([
                        'handle' => '$country',
                        'name' => Craft::t('formie', 'Country'),
                    ]),
                    new IntegrationField([
                        'handle' => '$zip',
                        'name' => Craft::t('formie', 'Zip'),
                    ]),
                    new IntegrationField([
                        'handle' => '$organization',
                        'name' => Craft::t('formie', 'Organization'),
                    ]),
                    new IntegrationField([
                        'handle' => '$title',
                        'name' => Craft::t('formie', 'Title'),
                    ]),
                ];

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['list_id'],
                    'name' => $list['list_name'],
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

            // Create the profile first, with the Public API
            $payload = [
                'token' => App::parseEnv($this->publicApiKey),
                'properties' => $fieldValues,
            ];

            $response = $this->deliverPayload($submission, 'identify', $payload);

            if ($response === false) {
                return true;
            }

            // Subscribe the user to the list
            $email = ArrayHelper::remove($fieldValues, '$email');

            $payload = [
                'profiles' => [['email' => $email]],
            ];

            $response = $this->deliverPayload($submission, "v2/list/{$this->listId}/subscribe", $payload);

            if ($response === false) {
                return true;
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
            $response = $this->request('GET', 'v2/lists');
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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://a.klaviyo.com/api/',
            'query' => [
                'api_key' => App::parseEnv($this->privateApiKey),
            ],
        ]);
    }
}