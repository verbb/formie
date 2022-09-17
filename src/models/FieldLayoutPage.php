<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ConditionsHelper;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;
use craft\models\FieldLayoutTab as CraftFieldLayoutTab;

use yii\base\InvalidConfigException;

class FieldLayoutPage extends CraftFieldLayoutTab
{
    // Properties
    // =========================================================================

    public ?PageSettings $settings = null;

    private ?array $_fields = null;
    private ?FieldLayout $_layout = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('settings', $config)) {
            if (is_string($config['settings'])) {
                $config['settings'] = new PageSettings(Json::decodeIfJson($config['settings']));
            }

            if (!($config['settings'] instanceof PageSettings)) {
                $config['settings'] = new PageSettings();
            }
        } else {
            $config['settings'] = new PageSettings();
        }

        parent::__construct($config);
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout The tab’s layout.
     * @throws InvalidConfigException if [[groupId]] is set but invalid
     */
    public function getLayout(): FieldLayout
    {
        if ($this->_layout !== null) {
            return $this->_layout;
        }

        if (($this->_layout = Formie::$plugin->getFields()->getLayoutById($this->layoutId)) === null) {
            throw new InvalidConfigException('Invalid layout ID: ' . $this->layoutId);
        }

        return $this->_layout;
    }

    /**
     * Sets the page’s layout.
     *
     * @param FieldLayout $layout The page’s layout.
     */
    public function setLayout(CraftFieldLayout $layout): void
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the tab’s fields.
     *
     * @return FieldInterface[] The tab’s fields.
     * @throws InvalidConfigException
     */
    public function getCustomFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $this->_fields = [];

        if ($layout = $this->getLayout()) {
            foreach ($layout->getCustomFields() as $field) {
                /** @var Field $field */
                if ($field->tabId == $this->id) {
                    $this->_fields[] = $field;
                }
            }
        }

        return $this->_fields;
    }

    public function setCustomFields(array $fields): void
    {
        ArrayHelper::multisort($fields, 'sortOrder');
        $this->_fields = $fields;

        $elements = [];

        foreach ($this->_fields as $field) {
            $elements[] = Craft::createObject([
                'class' => CustomField::class,
                'required' => $field->required,
            ], [
                $field,
            ]);
        }

        $this->elements = $elements;
    }

    /**
     * @param bool $includeDisabled
     * @return FieldInterface[]
     * @throws InvalidConfigException
     */
    public function getRows(bool $includeDisabled = true): array
    {
        /* @var FormFieldInterface[] $pageFields */
        $pageFields = $this->getCustomFields();

        foreach ($pageFields as $key => $field) {
            if ($includeDisabled === false && $field->visibility === 'disabled') {
                unset($pageFields[$key]);
            }
        }

        return Formie::$plugin->getFields()->groupIntoRows($pageFields);
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
        return ($this->settings->enablePageConditions && $this->getConditions());
    }

    public function getConditions(): array
    {
        // Filter out any un-set conditions
        $conditions = $this->settings->pageConditions ?? [];
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
            // Find all errors that match field handles in this page
            $fieldHandles = ArrayHelper::getColumn($this->getCustomFields(), 'handle');

            foreach ($submission->getErrors() as $fieldHandle => $submissionError) {
                if (in_array($fieldHandle, $fieldHandles)) {
                    $errors[$fieldHandle] = $submissionError;
                }
            }
        }

        return $errors;
    }
}
