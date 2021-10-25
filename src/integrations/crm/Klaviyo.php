<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
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
use craft\helpers\StringHelper;
use craft\web\View;

class Klaviyo extends Crm
{
    // Properties
    // =========================================================================

    public $publicApiKey;
    public $privateApiKey;
    public $mapToProfile = false;
    public $profileFieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Klaviyo');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Klaviyo customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publicApiKey', 'privateApiKey'], 'required'];

        $profile = $this->getFormSettingValue('profile');

        // Validate the following when saving form settings
        $rules[] = [['profileFieldMapping'], 'validateFieldMapping', 'params' => $profile, 'when' => function($model) {
            return $model->enabled && $model->mapToProfile;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $profileFields = [
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

            $settings = [
                'profile' => $profileFields,
            ];
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
            $profileValues = $this->getFieldMappingValues($submission, $this->profileFieldMapping, 'profile');

            $profilePayload = [
                'token' => Craft::parseEnv($this->publicApiKey),
                'properties' => $profileValues,
            ];

            $response = $this->deliverPayload($submission, 'identify', $profilePayload);

            if ($response === false) {
                return true;
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
            $response = $this->request('GET', 'v2/lists');
        } catch (\Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://a.klaviyo.com/api/',
            'query' => [
                'api_key' => Craft::parseEnv($this->privateApiKey),
            ],
        ]);

        return $this->_client;
    }
}
