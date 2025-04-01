<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\MissingField;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;

use Craft;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\base\SavableComponent;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Component;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;

use yii\base\InvalidConfigException;

use DateTime;

class FieldLayoutRow extends SavableComponent
{
    // Properties
    // =========================================================================

    // public ?int $ownerId = null;
    public ?int $layoutId = null;
    public ?int $pageId = null;
    public ?int $sortOrder = null;
    public ?string $uid = null;

    private ?Form $_form = null;
    private ?FieldLayout $_layout = null;
    private ?FieldLayoutPage $_page = null;
    private array $_fields = [];


    // Public Methods
    // =========================================================================

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

    public function getPage(): ?FieldLayoutPage
    {
        if ($this->_page || !$this->pageId) {
            return $this->_page;
        }

        return $this->_page = Formie::$plugin->getFields()->getPageById($this->pageId);
    }

    public function getFields(bool $includeDisabled = true, bool $includeHidden = true): array
    {
        $fields = $this->_fields;

        foreach ($this->_fields as $fieldKey => $field) {
            if (!$includeDisabled && $field->getIsDisabled()) {
                unset($fields[$fieldKey]);
            }

            if (!$includeHidden && $field->getIsHidden()) {
                unset($fields[$fieldKey]);
            }
        }

        return $fields;
    }

    public function setFields(array $fields): void
    {
        $this->_fields = [];

        $fieldsService = Formie::$plugin->getFields();

        foreach ($fields as $field) {
            $this->_fields[] = $field instanceof FieldInterface ? $field : $fieldsService->createField($field);
        }
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
        Craft::$app->getDeprecator()->log(__METHOD__, 'Row’s `getCustomFields()` method has been deprecated. Use `getFields()` instead.');

        return $this->getFields();
    }

    public function getIsHidden(): bool
    {
        $fields = [];

        foreach ($this->getFields(false) as $field) {
            if (!$field->getIsHidden()) {
                $fields[] = $field;
            }
        }

        return !(bool)$fields;
    }

    public function getFormBuilderConfig(): array
    {
        return [
            'id' => $this->id,
            'layoutId' => $this->layoutId,
            'pageId' => $this->pageId,
            'errors' => $this->getErrors(),
            'fields' => array_map(function($field) {
                return $field->getFormBuilderConfig();
            }, $this->getFields()),
        ];
    }

    public function validateFields(): void
    {
        foreach ($this->getFields() as $field) {
            if (!$field->validate()) {
                $this->addError('fields', $field->getErrors());
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['fields'], 'validateFields'];

        return $rules;
    }
}
