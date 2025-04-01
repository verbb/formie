<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\StringHelper;

use Craft;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\base\SavableComponent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;

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
        // Auto-generated for the moment
        return StringHelper::toCamelCase($this->label);
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

        foreach ($rows as $row) {
            $this->_rows[] = (!($row instanceof FieldLayoutRow)) ? new FieldLayoutRow($row) : $row;
        }
    }

    public function getFields(bool $includeDisabled = true): array
    {
        $fields = [];

        foreach ($this->getRows($includeDisabled) as $row) {
            foreach ($row->getFields($includeDisabled) as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        $foundField = null;

        foreach ($this->getFields() as $field) {
            if ($field->handle === $handle) {
                $foundField = $field;
            }
        }

        return $foundField;
    }

    public function getCustomFields(): array
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Page’s `getCustomFields()` method has been deprecated. Use `getFields()` instead.');

        return $this->getFields();
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'layoutId' => $this->layoutId,
            'label' => $this->label,
            'settings' => $this->getPageSettings()?->toArray(),
            'sortOrder' => $this->sortOrder,
            'errors' => $this->getErrors(),
            'rows' => array_map(function($row) {
                return $row->getFormBuilderConfig();
            }, $this->getRows()),
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

    public function getConditionsJson(): ?string
    {
        if ($this->hasConditions()) {
            $conditionSettings = $this->getConditions();
            $conditions = $conditionSettings['conditions'] ?? [];

            // Prep the conditions for JS
            $conditionSettings['conditions'] = ConditionsHelper::prepConditionsForJs($conditions);

            return Json::encode($conditionSettings);
        }

        return null;
    }

    public function getFieldErrors(?Submission $submission): array
    {
        $errors = [];

        // Ensure that we recursively check for nested/subfields for errors
        $getFieldErrors = function(array $fields) use ($submission, &$errors, &$getFieldErrors) {
            foreach ($fields as $field) {
                $errors[$field->fieldKey] = $submission->getErrors()[$field->fieldKey] ?? null;

                if ($field instanceof NestedFieldInterface) {
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
