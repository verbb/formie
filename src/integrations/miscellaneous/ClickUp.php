<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use GuzzleHttp\Client;

use Throwable;

class ClickUp extends Miscellaneous
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'ClickUp');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $workspaceId = null;
    public ?string $listId = null;
    public ?array $fieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to ClickUp.');
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $listOptions = [];
            $workspaceId = App::parseEnv($this->workspaceId);

            $response = $this->request('GET', "team/$workspaceId/space");

            $spaces = $response['spaces'] ?? [];

            foreach ($spaces as $space) {
                $spaceId = $space['id'];

                $response = $this->request('GET', "space/$spaceId/folder");
                $folders = $response['folders'] ?? [];

                foreach ($folders as $folder) {
                    $folderId = $folder['id'];
                    $lists = $folder['lists'] ?? [];

                    foreach ($lists as $list) {
                        $listId = $list['id'];


                        $listOptions[] = [
                            'name' => $space['name'] . ': ' . $folder['name'] . ': ' . $list['name'],
                            'id' => $space['id'] . ':' . $folder['id'] . ':' . $list['id'],
                            'fields' => $this->_getCustomFields($listId),
                        ];
                    }
                }

                // Get any folderless lists
                $response = $this->request('GET', "space/$spaceId/list");
                $lists = $response['lists'] ?? [];

                foreach ($lists as $list) {
                    $listId = $list['id'];

                    $listOptions[] = [
                        'name' => $list['space']['name'] . ': ' . $list['name'],
                        'id' => $list['space']['id'] . ':' . $list['id'],
                        'fields' => $this->_getCustomFields($listId),
                    ];
                }
            }

            $settings = [
                'lists' => $listOptions,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fields = $this->_getListSettings()['fields'] ?? [];
            $listValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            $listIds = explode(':', $this->listId);
            $listId = array_pop($listIds);

            if (!$listId) {
                Integration::error($this, Craft::t('formie', 'Missing mapped “listId” {id}', [
                    'id' => $this->listId,
                ]), true);

                return false;
            }

            $payload = $this->_prepContactPayload($listValues);

            $response = $this->deliverPayload($submission, "list/$listId/task", $payload);

            if ($response === false) {
                return true;
            }

            $taskId = $response['id'] ?? '';

            if (!$taskId) {
                Integration::error($this, Craft::t('formie', 'Missing return “taskId” {response}. Sent payload {payload}', [
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
            $workspaceId = App::parseEnv($this->workspaceId);

            $response = $this->request('GET', "team/$workspaceId/space");
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey', 'workspaceId'], 'required'];

        $fields = $this->_getListSettings()->fields ?? [];

        // Validate the following when saving form settings
        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.clickup.com/api/v2/',
            'headers' => ['Authorization' => App::parseEnv($this->apiKey)],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _convertFieldType(string $fieldType): string
    {
        $fieldTypes = [
            'date' => IntegrationField::TYPE_DATE,
            'datetime' => IntegrationField::TYPE_DATETIME,
            'number' => IntegrationField::TYPE_NUMBER,
        ];

        return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
    }

    private function _getCustomFields(string $listId): array
    {
        $customFields = [
            new IntegrationField([
                'handle' => 'name',
                'name' => Craft::t('formie', 'Name'),
                'required' => true,
            ]),
            new IntegrationField([
                'handle' => 'description',
                'name' => Craft::t('formie', 'Description'),
            ]),
            new IntegrationField([
                'handle' => 'status',
                'name' => Craft::t('formie', 'Status'),
            ]),
            new IntegrationField([
                'handle' => 'priority',
                'name' => Craft::t('formie', 'Priority'),
            ]),
        ];

        $response = $this->request('GET', "list/$listId/field");
        $fields = $response['fields'] ?? [];

        foreach ($fields as $field) {
            $customFields[] = new IntegrationField([
                'handle' => 'custom:' . $field['id'],
                'name' => $field['name'],
                'type' => $this->_convertFieldType($field['type']),
                'sourceType' => $field['type'],
                'required' => $field['required'],
            ]);
        }

        return $customFields;
    }

    private function _getListSettings(): array
    {
        $lists = $this->getFormSettingValue('lists');

        if ($list = ArrayHelper::firstWhere($lists, 'id', $this->listId)) {
            return $list;
        }

        return [];
    }

    private function _prepContactPayload($fields): array
    {
        $payload = $fields;

        foreach ($payload as $key => $value) {
            if (StringHelper::startsWith($key, 'custom:')) {
                $field = ArrayHelper::remove($payload, $key);

                $payload['custom_fields'][] = [
                    'id' => str_replace('custom:', '', $key),
                    'value' => $value,
                ];
            }
        }

        return $payload;
    }
}