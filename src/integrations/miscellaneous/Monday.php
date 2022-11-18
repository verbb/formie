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
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use GuzzleHttp\Client;

use Throwable;

class Monday extends Miscellaneous
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Monday');
    }
    

    // Properties
    // =========================================================================
    
    public ?string $apiKey = null;
    public ?string $boardId = null;
    public ?array $fieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to Monday.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $fields = $this->_getBoardSettings()->fields ?? [];

        // Validate the following when saving form settings
        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            $boards = $this->_getPaginated();
            $boardOptions = [];

            foreach ($boards as $board) {
                $groups = $board['groups'] ?? [];
                $columns = $board['columns'] ?? [];

                foreach ($groups as $group) {
                    $boardOptions[] = [
                        'name' => $board['name'] . ': ' . $group['title'],
                        'id' => $board['id'] . ':' . $group['id'],
                        'fields' => $this->_getCustomFields($columns),
                    ];
                }
            }

            $settings = [
                'boards' => $boardOptions,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fields = $this->_getBoardSettings()->fields ?? [];
            $boardValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            $boardIds = explode(':', $this->boardId);
            $boardId = $boardIds[0] ?? '';
            $groupId = $boardIds[1] ?? '';

            if (!$boardId) {
                Integration::error($this, Craft::t('formie', 'Missing mapped “boardId” {id}', [
                    'id' => $this->boardId,
                ]), true);

                return false;
            }

            if (!$groupId) {
                Integration::error($this, Craft::t('formie', 'Missing mapped “groupId” {id}', [
                    'id' => $this->boardId,
                ]), true);

                return false;
            }

            $itemPayload = [
                'query' => '
                    mutation CreateItemMutation($boardId: Int!, $groupId: String, $itemName: String, $columns: JSON) {
                        create_item(board_id: $boardId, group_id: $groupId, item_name: $itemName, column_values: $columns) {
                            id
                        }
                    }
                ',
                'operationName' => 'CreateItemMutation',
                'variables' => [
                    'boardId' => (int)$boardId,
                    'groupId' => $groupId,
                    'itemName' => ArrayHelper::remove($boardValues, 'name:name'),
                    'columns' => $this->_prepColumns($boardValues),
                ],
            ];

            $response = $this->deliverPayload($submission, '/', $itemPayload);

            if ($response === false) {
                return true;
            }

            $itemId = $response['data']['create_item']['id'] ?? '';

            if (!$itemId) {
                Integration::error($this, Craft::t('formie', 'Missing return “itemId” {response}', [
                    'response' => Json::encode($response),
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
            $response = $this->request('POST', '/', [
                'form_params' => [
                    'query' => '
                        query {
                            me {
                                is_guest
                                join_date
                            }
                        }
                    ',
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
            'base_uri' => 'https://api.monday.com/v2/',
            'headers' => [
                'Authorization' => App::parseEnv($this->apiKey),
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getCustomFields($columns, $excludeNames = []): array
    {
        $columnOptions = [];

        foreach ($columns as $key => $column) {
            $options = [];
            $required = false;

            if ($column['type'] === 'name') {
                $required = true;
            }

            if ($column['type'] === 'color') {
                $settings = Json::decode($column['settings_str']);
                $labels = $settings['labels'] ?? [];

                foreach ($labels as $labelId => $label) {
                    $options[] = [
                        'label' => $label,
                        'value' => $labelId,
                    ];
                }
            }

            if ($options) {
                $options = [
                    'label' => $column['title'],
                    'options' => $options,
                ];
            }


            $columnOptions[] = new IntegrationField([
                'handle' => $column['type'] . ':' . $column['id'],
                'name' => $column['title'],
                'type' => $column['type'],
                'required' => $required,
                'options' => $options,
            ]);
        }

        return $columnOptions;
    }

    private function _prepColumns($columns): string
    {
        $newColumns = [];

        // Prepare columns for the API - we keep a record of the type when mapping
        foreach ($columns as $key => $value) {
            $columnInfo = explode(':', $key);
            [$type, $handle] = $columnInfo;

            if ($type === 'email') {
                $newColumns[$handle] = [
                    'email' => $value,
                    'text' => $value,
                ];
            } else if ($type === 'link') {
                $newColumns[$handle] = [
                    'url' => $value,
                    'text' => $value,
                ];
            } else if ($type === 'phone') {
                $newColumns[$handle] = [
                    'phone' => $value,
                    'countryShortName' => '',
                ];
            } else if ($type === 'color') {
                $newColumns[$handle] = [
                    'index' => (int)$value,
                ];
            } else if ($type === 'lookup') {
                // No supported in API
            } else if ($type === 'board-relation') {
                // No supported in API
            } else if ($type === 'date') {
                $date = DateTimeHelper::toDateTime($value);

                if ($date) {
                    $newColumns[$handle] = [
                        'date' => $date->format('Y-m-d'),
                        'time' => $date->format('H:i:s'),
                    ];
                } else {
                    $newColumns[$handle] = $value;
                }
            } else {
                $newColumns[$handle] = $value;
            }
        }

        return Json::encode($newColumns);
    }

    private function _getBoardSettings()
    {
        $boards = $this->getFormSettingValue('boards');

        if ($board = ArrayHelper::firstWhere($boards, 'id', $this->boardId)) {
            return $board;
        }

        return [];
    }

    private function _getPaginated($limit = 100, $page = 1, $items = [])
    {
        $response = $this->request('POST', '/', [
            'form_params' => [
                'query' => '
                    query {
                        boards (limit:' . $limit . ', page:' . $page . ') {
                            name
                            id

                            groups {
                                id
                                title
                            }

                            columns {
                                id
                                title
                                type
                                settings_str
                            }
                        }
                    }
                ',
            ],
        ]);

        $newItems = $response['data']['boards'] ?? [];
        $nextPage = $page + 1;

        if ($newItems) {
            $items = array_merge($items, $newItems);

            if (count($newItems) === $limit) {
                $items = $this->_getPaginated($limit, $nextPage, $items);
            }
        }

        return $items;
    }
}
