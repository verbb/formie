<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\base\Integration;
use verbb\formie\base\EmailMarketing;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;

use GuzzleHttp\Client;

use Throwable;

class Klaviyo extends EmailMarketing
{
    // Static Methods
    // =========================================================================

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
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $lists = $this->_getPaginated('lists');

            foreach ($lists as $list) {
                $listFields = [
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
                    new IntegrationField([
                        'handle' => 'source',
                        'name' => Craft::t('formie', 'Source'),
                    ]),
                    new IntegrationField([
                        'handle' => 'sms_consent',
                        'name' => Craft::t('formie', 'Consent To Send SMS'),
                        'type' => IntegrationField::TYPE_BOOLEAN,
                        'options' => [
                            'label' => Craft::t('formie', 'Consent To Send SMS'),
                            'options' => [
                                [
                                    'label' =>  Craft::t('formie', 'Yes'),
                                    'value' => true,
                                ],
                                [
                                    'label' =>  Craft::t('formie', 'No'),
                                    'value' => false,
                                ],
                            ],
                        ],
                    ]),
                ];

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['id'],
                    'name' => $list['attributes']['name'],
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

            // Location values should be separate
            $location = array_filter([
                'address1' => ArrayHelper::remove($fieldValues, 'address1'),
                'address2' => ArrayHelper::remove($fieldValues, 'address2'),
                'city' => ArrayHelper::remove($fieldValues, 'city'),
                'region' => ArrayHelper::remove($fieldValues, 'region'),
                'zip' => ArrayHelper::remove($fieldValues, 'zip'),
                'country' => ArrayHelper::remove($fieldValues, 'country'),
            ]);

            if ($location) {
                $fieldValues['location'] = $location;
            }

            // Create or update a Profile first
            $payload = [
                'data' => [
                    'type' => 'profile',
                    'attributes' => $fieldValues,
                ],
            ];

            $response = $this->deliverPayload($submission, 'profile-import', $payload);

            if ($response === false) {
                return true;
            }

            $profileId = $response['data']['id'] ?? '';

            if (!$profileId) {
                Integration::error($this, Craft::t('formie', 'Missing return “profileId” {response}. Sent payload {payload}', [
                    'response' => Json::encode($response),
                    'payload' => Json::encode($payload),
                ]), true);

                return false;
            }

            // Extract any consent settings
            $smsConsent = ArrayHelper::remove($fieldValues, 'sms_consent');

            // A profile subscription only allows a subset of information from the profile mapping
            $profile = array_filter([
                'email' => $fieldValues['email'] ?? null,
                'phone_number' => $fieldValues['phone_number'] ?? null,
            ]);

            $profile['subscriptions']['email']['marketing']['consent'] = 'SUBSCRIBED';

            if ($smsConsent) {
                $profile['subscriptions']['sms']['marketing']['consent'] = 'SUBSCRIBED';
            }

            // Subscribe the user to the list
            $payload = [
                'data' => [
                    'type' => 'profile-subscription-bulk-create-job',
                    'attributes' => [
                        'profiles' => [
                            'data' => [
                                [
                                    'type' => 'profile',
                                    'id' => $profileId,
                                    'attributes' => $profile,
                                ],
                            ],
                        ],
                    ],
                    'relationships' => [
                        'list' => [
                            'data' => [
                                'type' => 'list',
                                'id' => $this->listId,
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->deliverPayload($submission, 'profile-subscription-bulk-create-jobs', $payload);

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

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getPaginated(string $endpoint, int $limit = 10, ?string $cursor = null, array $items = []): array
    {
        $response = $this->request('GET', $endpoint, [
            'query' => [
                'sort' => 'name',
                'page' => [
                    'cursor' => $cursor,
                ],
            ],
        ]);

        $newItems = $response['data'] ?? [];
        $cursor = $response['links']['next'] ?? 0;

        $items = array_merge($items, $newItems);

        if ($cursor) {
            // Extract cursor from `https://a.klaviyo.com/api/lists?sort=name&page%5Bcursor%5D=bmV4dDo6bmFtZTo6VGVzdCA2`
            parse_str(parse_url($cursor, PHP_URL_QUERY), $query);
            $cursor = $query['page']['cursor'];

            $items = $this->_getPaginated($endpoint, $limit, $cursor, $items);
        }

        return $items;
    }
}