<?php
namespace verbb\formie\integrations\crm;

use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;

use GuzzleHttp\Client;

use Throwable;

class Klaviyo extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Klaviyo');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $publicApiKey = null;
    public ?string $privateApiKey = null;
    public bool $mapToProfile = false;
    public ?array $profileFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your {name} customers by providing important information on their conversion on your site.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            if ($this->mapToProfile) {
                $settings['profile'] = [
                    new IntegrationField([
                        'handle' => 'first_name',
                        'name' => Craft::t('formie', 'First Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'last_name',
                        'name' => Craft::t('formie', 'Last Name'),
                    ]),
                    new IntegrationField([
                        'handle' => 'email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'phone_number',
                        'name' => Craft::t('formie', 'Phone Number'),
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
                        'handle' => 'region',
                        'name' => Craft::t('formie', 'Region'),
                    ]),
                    new IntegrationField([
                        'handle' => 'country',
                        'name' => Craft::t('formie', 'Country'),
                    ]),
                    new IntegrationField([
                        'handle' => 'zip',
                        'name' => Craft::t('formie', 'Zip'),
                    ]),
                    new IntegrationField([
                        'handle' => 'organization',
                        'name' => Craft::t('formie', 'Organization'),
                    ]),
                    new IntegrationField([
                        'handle' => 'title',
                        'name' => Craft::t('formie', 'Title'),
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
            $profileValues = $this->getFieldMappingValues($submission, $this->profileFieldMapping, 'profile');

            // Location values should be separate
            $location = array_filter([
                'address1' => ArrayHelper::remove($profileValues, 'address1'),
                'address2' => ArrayHelper::remove($profileValues, 'address2'),
                'city' => ArrayHelper::remove($profileValues, 'city'),
                'region' => ArrayHelper::remove($profileValues, 'region'),
                'zip' => ArrayHelper::remove($profileValues, 'zip'),
                'country' => ArrayHelper::remove($profileValues, 'country'),
            ]);

            if ($location) {
                $profileValues['location'] = $location;
            }

            $profilePayload = [
                'data' => [
                    'type' => 'profile',
                    'attributes' => $profileValues,
                ],
            ];

            $response = $this->deliverPayload($submission, 'profile-import', $profilePayload);

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
            $response = $this->request('GET', 'lists');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://a.klaviyo.com/api/',
            'headers' => [
                'Authorization' => 'Klaviyo-API-Key ' . App::parseEnv($this->privateApiKey),
                'revision' => '2024-05-15',
            ],
        ]);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['publicApiKey', 'privateApiKey'], 'required'];

        $profile = $this->getFormSettingValue('profile');

        // Validate the following when saving form settings
        $rules[] = [
            ['profileFieldMapping'], 'validateFieldMapping', 'params' => $profile, 'when' => function($model) {
                return $model->enabled && $model->mapToProfile;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }
}
