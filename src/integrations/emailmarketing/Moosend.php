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
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Moosend extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Moosend');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Moosend lists to grow your audience for campaigns.');
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
            $response = $this->request('GET', 'lists.json');

            $lists = $response['Context']['MailingLists'] ?? [];

            foreach ($lists as $list) {
                // While we're at it, fetch the fields for the list
                $listFields = [
                    new IntegrationField([
                        'handle' => 'Email',
                        'name' => Craft::t('formie', 'Email'),
                        'required' => true,
                    ]),
                    new IntegrationField([
                        'handle' => 'Name',
                        'name' => Craft::t('formie', 'Name'),
                    ]),
                ];

                $fields = $list['CustomFieldsDefinition'] ?? [];

                foreach ($fields as $field) {
                    $listFields[] = new IntegrationField([
                        'handle' => $field['Name'],
                        'name' => $field['Name'],
                        'required' => $field['IsRequired'],
                    ]);
                }

                $settings['lists'][] = new IntegrationCollection([
                    'id' => $list['ID'],
                    'name' => $list['Name'],
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
            $email = ArrayHelper::remove($fieldValues, 'Email');
            $name = ArrayHelper::remove($fieldValues, 'Name');

            // Format custom fields
            $customFields = [];

            foreach ($fieldValues as $key => $value) {
                $customFields[] = $key . '=' . $value;
            }

            $payload = [
                'Email' => $email,
                'Name' => $name,
                'CustomFields' => $customFields,
            ];

            $response = $this->deliverPayload($submission, "subscribers/{$this->listId}/subscribe.json", $payload);

            if ($response === false) {
                return true;
            }

            $contactId = $response['Context']['ID'] ?? '';

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

    public function fetchConnection(): bool
    {
        try {
            $response = $this->request('GET', 'lists.json');
            $accountId = $response['Context']['MailingLists'][0]['ID'] ?? '';

            if (!$accountId) {
                Integration::error($this, 'Unable to find “{instance_id}” in response.', true);
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

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'http://api.moosend.com/v3/',
            'query' => ['apikey' => App::parseEnv($this->apiKey)],
        ]);
    }
}