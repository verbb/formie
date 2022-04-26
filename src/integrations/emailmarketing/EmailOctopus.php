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

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

use Throwable;

class EmailOctopus extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'EmailOctopus');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your EmailOctopus lists to grow your audience for campaigns.');
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
            $response = $this->request('GET', 'lists', [
                'query' => [
                    'api_key' => App::parseEnv($this->apiKey),
                ],
            ]);

            $lists = $response['data'] ?? [];

            foreach ($lists as $list) {
                $listFields = $this->_getCustomFields($list['fields']);

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
            $errorCode = '';

            // Pull out email, as it needs to be top level
            $email = ArrayHelper::remove($fieldValues, 'EmailAddress');
            $emailHash = md5(strtolower($email));

            $payload = [
                'api_key' => App::parseEnv($this->apiKey),
                'email_address' => $email,
                'status' => 'SUBSCRIBED',
                'fields' => $fieldValues,
            ];

            // An error will be thrown if a user already exists
            try {
                $response = $this->deliverPayload($submission, "lists/{$this->listId}/contacts", $payload);
            } catch (Throwable $exception) {
                if ($exception instanceof RequestException && $response = $exception->getResponse()) {
                    $message = Json::decode((string)$response->getBody());
                    $errorCode = $message['error']['code'] ?? '';
                }
            }

            // If there was an error that the user already exists, update it
            if ($errorCode === 'MEMBER_EXISTS_WITH_EMAIL_ADDRESS') {
                $response = $this->deliverPayload($submission, "lists/{$this->listId}/contacts/$emailHash", $payload, 'PUT');
            }

            if ($response === false) {
                return true;
            }

            $contactId = $response['id'] ?? '';

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
            $response = $this->request('GET', 'lists', [
                'query' => [
                    'api_key' => App::parseEnv($this->apiKey),
                ],
            ]);
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
            'base_uri' => 'https://emailoctopus.com/api/1.5/',
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType($fieldType)
    {
        $fieldTypes = [
            'NUMBER' => IntegrationField::TYPE_NUMBER,
            'DATE' => IntegrationField::TYPE_DATETIME,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields($fields): array
    {
        $customFields = [];

        foreach ($fields as $key => $field) {
            $required = false;

            if ($field['tag'] == 'EmailAddress') {
                $required = true;
            }

            $customFields[] = new IntegrationField([
                'handle' => $field['tag'],
                'name' => $field['label'],
                'type' => $this->_convertFieldType($field['type']),
                'required' => $required,
            ]);
        }

        return $customFields;
    }
}
