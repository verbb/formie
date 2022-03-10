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

use GuzzleHttp\Client;

use Throwable;

class Adestra extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Adestra');
    }

    // Properties
    // =========================================================================

    
    public ?string $apiKey = null;
    public ?string $coreTableId = null;
    public ?string $workspaceId = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Adestra lists to grow your audience for campaigns.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'workspaceId', 'coreTableId'], 'required'];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $response = $this->request('GET', '/core_tables/' . App::parseEnv($this->coreTableId));
            $fields = $response['table_columns'] ?? [];

            $listFields = [];

            foreach ($fields as $field) {
                $listFields[] = new IntegrationField([
                    'handle' => $field['name'],
                    'name' => $field['name'],
                    'type' => IntegrationField::TYPE_STRING,
                ]);
            }

            $response = $this->request('GET', 'lists', [
                'query' => [
                    'search:workspace_id' => App::parseEnv($this->workspaceId),
                    'search:table_id' => App::parseEnv($this->coreTableId),
                    'paging:page_size' => 250,
                ],
            ]);

            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['name'] . ' (' . $list['id'] . ')',
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
            $contactData = [];

            foreach ($fieldValues as $name => $value) {
                $contactData[App::parseEnv($this->coreTableId) . '.' . $name] = $value;
            }

            $payload = [
                'table_id' => (int)App::parseEnv($this->coreTableId),
                'dedupe_field' => 'email',
                'options' => [
                    'list_id' => (int)$this->listId,
                ],
                'contact_data' => $contactData,
            ];

            $response = $this->deliverPayload($submission, 'contacts', $payload);

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
            $response = $this->request('GET', '/workspaces/' . App::parseEnv($this->workspaceId));
            $workspaceName = $response['name'] ?? '';

            if (!$workspaceName) {
                Integration::error($this, 'Unable to find “{name}” in response.', true);
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
            'base_uri' => 'https://app.adestra.com/api/rest/1/',
            'headers' => [
                'Authorization' => 'TOKEN ' . App::parseEnv($this->apiKey),
            ],
        ]);
    }
}