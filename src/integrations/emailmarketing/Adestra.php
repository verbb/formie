<?php
namespace verbb\formie\integrations\emailmarketing;

use verbb\formie\attributes\Sensitive;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\App;

use Throwable;

use GuzzleHttp\Client;

class Adestra extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Adestra';
    }


    // Properties
    // =========================================================================

    #[Sensitive]
    public ?string $apiKey = null;
    public ?string $coreTableId = null;
    public ?string $workspaceId = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your {name} lists to grow your audience for campaigns.', ['name' => static::displayName()]);
    }

    public function fetchConfig(): IntegrationConfig
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

        return new IntegrationConfig($settings);
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


    // Protected Methods
    // =========================================================================

    protected function executePayload(Submission $submission): IntegrationResult
    {
        $this->beginPayloadDelivery($submission);

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
                return $this->resultForPayload(true);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return $this->resultForPayload(false);
        }

        return $this->resultForPayload(true);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'workspaceId', 'coreTableId'], 'required'];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://app.adestra.com/api/rest/1/',
            'headers' => [
                'Authorization' => 'TOKEN ' . App::parseEnv($this->apiKey),
            ],
        ]);
    }
}
