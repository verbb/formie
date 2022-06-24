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
    // Public Properties
    // =========================================================================

    /**
     * @var PageSettings
     */
    public $settings;


    // Private Properties
    // =========================================================================

    private $_layout;
    private $_fields;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->settings)) {
            $this->settings = new PageSettings();
        } else {
            $settings = Json::decodeIfJson($this->settings);
            $this->settings = new PageSettings($settings);
        }
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout|null The tab’s layout.
     * @throws InvalidConfigException if [[groupId]] is set but invalid
     */
    public function getLayout()
    {
        if ($this->_layout !== null) {
            return $this->_layout;
        }

        if (!$this->layoutId) {
            return null;
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
    public function setLayout(CraftFieldLayout $layout)
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the tab’s fields.
     *
     * @return FieldInterface[] The tab’s fields.
     * @throws InvalidConfigException
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        $this->_fields = [];

        if ($layout = $this->getLayout()) {
            foreach ($layout->getFields() as $field) {
                /** @var Field $field */
                if ($field->tabId == $this->id) {
                    $this->_fields[] = $field;
                }
            }
        }

        return $this->_fields;
    }

    /**
     * @inheritDoc
     */
    public function setFields(array $fields)
    {
        ArrayHelper::multisort($fields, 'sortOrder');
        $this->_fields = $fields;

        $this->elements = [];

        foreach ($this->_fields as $field) {
            $this->elements[] = Craft::createObject([
                'class' => CustomField::class,
                'required' => $field->required,
            ], [
                $field,
            ]);
        }
    }

    /**
     * @return FieldInterface[]
     * @throws InvalidConfigException
     */
    public function getRows($includeDisabled = true)
    {
        /* @var FormFieldInterface[] $pageFields */
        $pageFields = $this->getFields($includeDisabled);

        foreach ($pageFields as $key => $field) {
            if ($includeDisabled === false && $field->visibility === 'disabled') {
                unset($pageFields[$key]);
            }
        }

        return Formie::$plugin->getFields()->groupIntoRows($pageFields);
    }

    /**
     * @inheritDoc
     */
    public function isConditionallyHidden($submission)
    {
        if ($this->settings->enablePageConditions) {
            $conditionSettings = Json::decode($this->settings->pageConditions) ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            if ($conditionSettings && $conditions) {
                // A `true` result means the field passed the evaluation and that it has a value, whilst a `false` result means
                // it didn't (for instance the field doesn't have a value)
                $result = ConditionsHelper::getConditionalTestResult($conditionSettings, $submission);

                // Depending on if we show or hide the field when evaluating. If `false` and set to show, it means
                // the field is hidden and the conditions to show it aren't met. Therefore, report back that this field is hidden.
                if (($result && $conditionSettings['showRule'] !== 'show') || (!$result && $conditionSettings['showRule'] === 'show')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getConditionsJson()
    {
        if ($this->settings->enablePageConditions) {
            $conditionSettings = Json::decode($this->settings->pageConditions) ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            // Prep the conditions for JS
            foreach ($conditions as &$condition) {
                ArrayHelper::remove($condition, 'id');

                // Dot-notation to name input syntax
                $condition['field'] = 'fields[' . str_replace(['{', '}', '.'], ['', '', ']['], $condition['field']) . ']';
            }

            $conditionSettings['conditions'] = $conditions;

            return Json::encode($conditionSettings);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getFieldErrors($submission)
    {
        $errors = [];

        if ($submission) {
            // Find all errors that match field handles in this page
            $fieldHandles = ArrayHelper::getColumn($this->getFields(), 'handle');

            foreach ($submission->getErrors() as $fieldHandle => $submissionError) {
                if (in_array($fieldHandle, $fieldHandles)) {
                    $errors[$fieldHandle] = $submissionError;
                }
            }
        }

        return $errors;
    }
}
