<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\StringHelper;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;

use yii\base\InvalidConfigException;

class FormPage extends Model
{
    // Properties
    // =========================================================================

    public ?string $label = null;

    private ?FormPageSettings $_settings = null;
    private array $_rows = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // No longer in use in Vue, but handle Formie 2 upgrades
        unset($config['id']);

        parent::__construct($config);
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'settings';
        $attributes[] = 'rows';
        
        return $attributes;
    }

    public function getHandle(): string
    {
        return StringHelper::toKebabCase($this->label);
    }

    public function getSettings(): ?FormPageSettings
    {
        return $this->_settings;
    }

    public function setSettings(array $settings): void
    {
        if (is_string($settings)) {
            $settings = new FormPageSettings(Json::decodeIfJson($settings));
        }

        if (!($settings instanceof FormPageSettings)) {
            $settings = new FormPageSettings();
        }

        $this->_settings = $settings;
    }

    public function getRows(bool $includeDisabled = true): array
    {
        // Filter out rows that have disabled/hidden fields
        if ($includeDisabled) {
            return $this->_rows;
        }

        $rows = [];

        foreach ($this->_rows as $rowKey => $row) {
            $fields = $row->getFields();
            $hiddenFields = [];

            foreach ($row->getFields() as $fieldKey => $field) {
                if ($field->visibility === 'disabled') {
                    $hiddenFields[] = $field;
                }
            }

            if (count($fields) === count($hiddenFields)) {
                unset($this->_rows[$rowKey]);
            }
        }

        return $this->_rows;
    }

    public function setRows(array $rows): void
    {
        foreach ($rows as $row) {
            $this->_rows[] = (!($row instanceof FormRow)) ? new FormRow($row) : $row;
        }
    }

    public function getFields(): array
    {
        $fields = [];

        foreach ($this->getRows() as $row) {
            foreach ($row->getFields() as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function getSerializedConfig(): array
    {
        return [
            'label' => $this->label,
            'settings' => $this->getSettings()?->toArray(),
            'rows' => array_map(function($row) {
                return $row->getSerializedConfig();
            }, $this->getRows()),
        ];
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'label' => $this->label,
            'settings' => $this->getSettings()?->toArray(),
            'errors' => $this->getErrors(),
            'rows' => array_map(function($row) {
                return $row->getFormBuilderConfig();
            }, $this->getRows()),
        ];
    }

    public function validateSettings(): void
    {
        $settings = $this->getSettings();

        if (!$settings->validate()) {
            $this->addError('settings', $settings->getErrors());
        }
    }

    public function validateRows(): void
    {
        foreach ($this->getRows() as $row) {
            if (!$row->validate()) {
                $this->addError('rows', $row->getErrors());
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
        return ($this->getSettings()->enablePageConditions && $this->getConditions());
    }

    public function getConditions(): array
    {
        // Filter out any un-set conditions
        $conditions = $this->getSettings()->pageConditions ?? [];
        $conditionRows = $conditions['conditions'] ?? [];

        foreach ($conditionRows as $key => $condition) {
            if (!($condition['condition'] ?? null)) {
                unset($conditions['conditions'][$key]);
            }
        }

        return $conditions;
    }

    public function getConditionsJson(): ?string
    {
        if ($this->hasConditions()) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            // Prep the conditions for JS
            foreach ($conditions as &$condition) {
                ArrayHelper::remove($condition, 'id');

                // Dot-notation to name input syntax
                $condition['field'] = 'fields[' . str_replace(['{', '}', '.'], ['', '', ']['], $condition['field']) . ']';
            }

            unset($condition);

            $conditionSettings['conditions'] = $conditions;

            return Json::encode($conditionSettings);
        }

        return null;
    }

    public function getFieldErrors(?Submission $submission): array
    {
        $errors = [];

        if ($submission) {
            foreach ($this->getFields() as $field) {
                $errors[$field->handle] = $submission->getErrors()[$field->handle] ?? null;
            }
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
