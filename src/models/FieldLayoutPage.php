<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\ValidationHelper;

use Craft;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\base\SavableComponent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;

use yii\base\InvalidConfigException;

use DateTime;

class FieldLayoutPage extends SavableComponent
{
    // Properties
    // =========================================================================

    public ?int $layoutId = null;
    public ?string $label = null;
    public ?int $sortOrder = null;
    public ?string $uid = null;

    private ?Form $_form = null;
    private ?FieldLayout $_layout = null;
    private ?FieldLayoutPageSettings $_pageSettings = null;
    private array $_rows = [];
    private ?array $_cachedFields = null;
    private ?array $_fieldsByHandle = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        if (array_key_exists('settings', $config)) {
            // Swap `settings` to `pageSettings` due to conflict with `SavableComponent::getSettings()` handling
            $config['pageSettings'] = ArrayHelper::remove($config, 'settings', []);
        }

        unset($config['enableConditions']);
        unset($config['notificationFlag']);

        parent::__construct($config);
    }

    public function getForm(): ?Form
    {
        if ($this->_form || !$this->layoutId) {
            return $this->_form;
        }

        return $this->_form = Formie::$plugin->getForms()->getFormByLayoutId($this->layoutId);
    }

    public function getLayout(): ?FieldLayout
    {
        if ($this->_layout || !$this->layoutId) {
            return $this->_layout;
        }

        return $this->_layout = Formie::$plugin->getFields()->getLayoutById($this->layoutId);
    }

    public function getHandle(): ?string
    {
        // Auto-generated for the moment.
        return StringHelper::toHandle((string)$this->label);
    }

    public function getSettings(): array
    {
        // Override `SavableComponent::getSettings()` behaviour to use our settings
        return $this->getPageSettings()?->toArray() ?? [];
    }

    public function getPageSettings(): ?FieldLayoutPageSettings
    {
        return $this->_pageSettings;
    }

    public function setPageSettings(array|string|null $pageSettings): void
    {
        if (is_string($pageSettings)) {
            $pageSettings = new FieldLayoutPageSettings(Json::decodeIfJson($pageSettings));
        }

        if (!($pageSettings instanceof FieldLayoutPageSettings)) {
            $pageSettings = new FieldLayoutPageSettings(($pageSettings ?? []));
        }

        $this->_pageSettings = $pageSettings;
    }

    public function getRows(bool $includeDisabled = true): array
    {
        $rows = $this->_rows;

        // Filter out rows that have disabled/hidden fields or are disabled altogether
        if ($includeDisabled) {
            return $rows;
        }

        foreach ($this->_rows as $rowKey => $row) {
            $fields = $row->getFields($includeDisabled);
            
            if (!$fields) {
                unset($rows[$rowKey]);
            }
        }

        return $rows;
    }

    public function setRows(array $rows): void
    {
        $this->_rows = [];
        $this->_cachedFields = null;
        $this->_fieldsByHandle = null;

        foreach ($rows as $row) {
            $this->_rows[] = (!($row instanceof FieldLayoutRow)) ? new FieldLayoutRow($row) : $row;
        }
    }

    public function getFields(bool $includeDisabled = true): array
    {
        if ($includeDisabled) {
            if ($this->_cachedFields === null) {
                $fields = [];

                foreach ($this->getRows() as $row) {
                    foreach ($row->getFields() as $field) {
                        $fields[] = $field;
                    }
                }

                $this->_cachedFields = $fields;
            }

            return $this->_cachedFields;
        }

        return array_values(array_filter($this->getFields(), static function(FieldInterface $field): bool {
            return !$field->getIsDisabled();
        }));
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        if ($this->_fieldsByHandle === null) {
            $this->_fieldsByHandle = [];

            foreach ($this->getFields() as $field) {
                $this->_fieldsByHandle[$field->handle] = $field;
            }
        }

        return $this->_fieldsByHandle[$handle] ?? null;
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'layoutId' => $this->layoutId,
            'label' => $this->label,
            '_handle' => $this->getHandle(),
            'settings' => $this->getPageSettings()?->toArray(),
            'sortOrder' => $this->sortOrder,
            'errors' => $this->getErrors(),
            'rows' => array_map(function($row) {
                return $row->getFormBuilderConfig();
            }, $this->getRows()),
        ];
    }

    public function getClientConfig(): array
    {
        return [
            'id' => (string)$this->id,
            'uid' => (string)$this->uid,
            'label' => $this->label,
            'settings' => $this->getSettings(),
            'fields' => array_map(static function(FieldInterface $field) {
                return $field->getClientConfig();
            }, $this->getFields(false)),
        ];
    }

    public function getClientPayload(Form $form, int $index): array
    {
        $pageSettings = $this->getPageSettings();
        $isLastPage = $form->isLastPage($this);
        $secondaryActions = [];

        if ($index > 0 && $pageSettings->showBackButton) {
            $secondaryActions[] = [
                'type' => 'back',
                'label' => $pageSettings->backButtonLabel,
            ];
        }

        if ($pageSettings->showSaveButton) {
            $secondaryActions[] = [
                'type' => 'save',
                'label' => $pageSettings->saveButtonLabel,
            ];
        }

        return [
            'id' => (string)$this->id,
            'key' => 'page-' . ($index + 1),
            'label' => $this->label,
            'condition' => ConditionsHelper::toComponentConditionDefinition($this->getClientConditions()),
            'rows' => array_values(array_map(static function(FieldLayoutRow $row) {
                return $row->getClientPayload();
            }, $this->getRows())),
            'actions' => [
                'primary' => [
                    'type' => $isLastPage ? 'submit' : 'next',
                    'label' => $pageSettings->submitButtonLabel,
                ],
                'secondary' => $secondaryActions,
            ],
        ];
    }

    public function validateSettings(): void
    {
        $settings = $this->getPageSettings();

        if (!$settings->validate()) {
            $this->addError('settings', $settings->getErrors());
        }
    }

    public function validateRows(): void
    {
        foreach ($this->getRows() as $rowKey => $row) {
            if (!$row->validate()) {
                ValidationHelper::addPrefixedErrors($this, $row->getErrors(), "rows.$rowKey");
            }
        }
    }

    public function isConditionallyHidden(Submission $submission): bool
    {
        if ($this->hasConditions()) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                // A `true` result means the field passed the evaluation and that it has a value, whilst a `false` result means
                // it didn't (for instance the field doesn't have a value)
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Depending on if we show or hide the field when evaluating. If `false` and set to show, it means
                // the field is hidden and the conditions to show it isn't met. Therefore, report back that this field is hidden.
                if (($result && $conditionSettings['showRule'] !== 'show') || (!$result && $conditionSettings['showRule'] === 'show')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasConditions(): bool
    {
        return ($this->getPageSettings()->enablePageConditions && $this->getConditions());
    }

    public function getConditions(): array
    {
        // Filter out any un-set conditions
        $conditions = $this->getPageSettings()->pageConditions ?? [];
        $conditionRows = $conditions['conditions'] ?? [];

        foreach ($conditionRows as $key => $condition) {
            if (!($condition['condition'] ?? null)) {
                unset($conditions['conditions'][$key]);
            }
        }

        return $conditions;
    }

    public function getClientConditions(): array
    {
        $conditions = $this->getConditions();

        if (!$conditions) {
            return [];
        }

        if ($form = $this->getForm()) {
            $conditions = ConditionsHelper::normalizeClientConditions($conditions, $form);
        }

        $conditions['clearOnHide'] = true;

        return $conditions;
    }

    public function getConditionsJson(): ?string
    {
        if (!$this->getPageSettings()->enablePageConditions) {
            return null;
        }

        $conditions = $this->getClientConditions();

        if (!$conditions) {
            return null;
        }

        return Json::encode($conditions);
    }

    public function hasSubmitButtonConditions(): bool
    {
        $pageSettings = $this->getPageSettings();

        return ($pageSettings->enableNextButtonConditions && $pageSettings->getConditions());
    }

    public function shouldRenderSubmitOnLastRow(bool $hasRows): bool
    {
        return $this->getPageSettings()?->shouldRenderSubmitOnLastRow($hasRows) ?? false;
    }

    public function isLastRow(FieldLayoutRow $row): bool
    {
        $rows = $this->getRows(false);

        if (!$rows) {
            return false;
        }

        $lastRow = $rows[array_key_last($rows)];

        if ($row === $lastRow) {
            return true;
        }

        if ($row->id !== null && $lastRow->id !== null && (string)$row->id === (string)$lastRow->id) {
            return true;
        }

        if ($row->uid !== null && $lastRow->uid !== null && (string)$row->uid === (string)$lastRow->uid) {
            return true;
        }

        return false;
    }

    public function getSubmitButtonConditions(): array
    {
        return $this->getPageSettings()->getConditions();
    }

    public function getSubmitButtonClientConditions(): array
    {
        $conditions = $this->getSubmitButtonConditions();

        if (!$conditions) {
            return [];
        }

        if ($form = $this->getForm()) {
            $conditions = ConditionsHelper::normalizeClientConditions($conditions, $form);
        }

        $conditions['clearOnHide'] = true;

        return $conditions;
    }

    public function getSubmitButtonConditionsJson(): ?string
    {
        if (!$this->getPageSettings()->enableNextButtonConditions) {
            return null;
        }

        $conditions = $this->getSubmitButtonClientConditions();

        if (!$conditions) {
            return null;
        }

        return Json::encode($conditions);
    }

    public function getFieldErrors(?Submission $submission): array
    {
        $errors = [];

        // Ensure that we recursively check for nested/subfields for errors
        $getFieldErrors = function(array $fields) use ($submission, &$errors, &$getFieldErrors) {
            foreach ($fields as $field) {
                $errors[$field->valueKey()] = $submission->getErrors()[$field->valueKey()] ?? null;

                if ($field instanceof ParentFieldInterface) {
                    $getFieldErrors($field->getFields());
                }
            }
        };

        if ($submission) {
            $getFieldErrors($this->getFields());
        }

        return array_filter($errors);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['label'], 'required'];
        $rules[] = [['settings'], 'validateSettings'];
        $rules[] = [['rows'], 'validateRows'];

        return $rules;
    }

}
