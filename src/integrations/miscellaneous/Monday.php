<?php
namespace verbb\formie\integrations\miscellaneous;

use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\View;

class Monday extends Miscellaneous
{
    // Properties
    // =========================================================================

    public $apiKey;
    public $boardId;
    public $fieldMapping;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Monday');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Monday customers by providing important information on their conversion on your site.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['apiKey'], 'required'];

        $boards = $this->getFormSettings()['boards'] ?? [];
        $boardFields = [];

        foreach ($boards as $board) {
            if ($board['id'] === $this->boardId) {
                $boardFields = $board['fields'];
            }
        }

        // Validate the following when saving form settings
        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $boardFields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function getFormSettings($useCache = true)
    {
        $settings = parent::getFormSettings($useCache);

        // Convert back to models from the cache
        foreach ($settings as $key => $setting) {
            foreach ($setting as $k => $value) {
                // Probably re-structure this for CRM's, but check if its a 'field'
                if (isset($value['handle'])) {
                    $settings[$key][$k] = new IntegrationField($value);
                } if (isset($value['fields'])) {
                    foreach ($value['fields'] as $i => $fieldConfig) {
                        $settings[$key][$k]['fields'][$i] = new IntegrationField($fieldConfig);
                    }
                }
            }
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $settings = [];

        try {
            $response = $this->request('POST', '/', [
                'form_params' => [
                    'query' => '
                        query {
                            boards {
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

            $boards = $response['data']['boards'] ?? [];
            $boardOptions = [];

            foreach ($boards as $key => $board) {
                $groups = $board['groups'] ?? [];
                $columns = $board['columns'] ?? [];

                foreach ($groups as $key => $group) {
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
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission): bool
    {
        try {
            $boardValues = $this->getFieldMappingValues($submission, $this->fieldMapping, 'boards');

            $boardIds = explode(':', $this->boardId);
            $boardId = $boardIds[0];
            $groupId = $boardIds[1];

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
                return false;
            }

            $itemId = $response['data']['create_item']['id'] ?? '';

            if (!$itemId) {
                Integration::error($this, Craft::t('formie', 'Missing return “itemId” {response}', [
                    'response' => Json::encode($response),
                ]), true);

                return false;
            }
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

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
        } catch (\Throwable $e) {
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]), true);

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.monday.com/v2/',
            'headers' => [
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _getCustomFields($columns, $excludeNames = [])
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

    /**
     * @inheritDoc
     */
    private function _prepColumns($columns)
    {
        $newColumns = [];

        // Prepare columns for the API - we keep a record of the type when mapping
        foreach ($columns as $key => $value) {
            $columnInfo = explode(':', $key);
            $type = $columnInfo[0];
            $handle = $columnInfo[1];

            if ($type === 'email') {
                $newColumns[$handle] = [
                    'email' => $value,
                    'text' => $value,
                ];
            } elseif ($type === 'link') {
                $newColumns[$handle] = [
                    'url' => $value,
                    'text' => $value,
                ];
            } elseif ($type === 'phone') {
                $newColumns[$handle] = [
                    'phone' => $value,
                    'countryShortName' => '',
                ];
            } elseif ($type === 'color') {
                $newColumns[$handle] = [
                    'index' => (int)$value,
                ];
            } elseif ($type === 'lookup') {
                // No supported in API
            } elseif ($type === 'board-relation') {
                // No supported in API
            } elseif ($type === 'date') {
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
}