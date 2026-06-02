<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\MissingField;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\ValidationHelper;

use Craft;
use craft\base\Field as CraftField;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\base\SavableComponent;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Component;
use craft\helpers\Json;

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
    private ?array $_fieldsByHandle = null;


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
        $fields = $this->_hydrateFields();

        foreach ($fields as $fieldKey => $field) {
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
        // Store raw field config payloads until the row is actually asked for field objects.
        // Formie layouts are much more form-specific than Craft's global field registry, so the lazy
        // seam lives here as well: layout hydration can stay cheap, while callers still get the same
        // concrete `FieldInterface` instances once they traverse into a row.
        $this->_fields = [];
        $this->_fieldsByHandle = null;

        foreach ($fields as $field) {
            $this->_fields[] = $field;
        }
    }

    public function withParentField(FieldInterface $parent, string|int|null $namespace = null): self
    {
        $row = clone $this;
        $row->_fields = array_map(
            static fn(FieldInterface $field) => $field->withParentField($parent, $namespace),
            $this->_hydrateFields(),
        );
        $row->_fieldsByHandle = null;

        return $row;
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

    public function getClientConfig(): array
    {
        return [
            'fields' => array_map(static function(FieldInterface $field) {
                return $field->getClientConfig();
            }, $this->getFields(false)),
        ];
    }

    public function getClientPayload(): array
    {
        return [
            'fields' => array_values(array_filter(array_map(static function(FieldInterface $field) {
                return $field->getClientPayload();
            }, $this->getFields(false)))),
        ];
    }

    public function validateFields(): void
    {
        foreach ($this->getFields() as $fieldKey => $field) {
            if (!$field->validate()) {
                ValidationHelper::addPrefixedErrors($this, $field->getErrors(), "fields.$fieldKey");
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


    // Private Methods
    // =========================================================================

    private function _hydrateFields(): array
    {
        $fieldsService = Formie::$plugin->getFields();

        foreach ($this->_fields as $fieldKey => $field) {
            if ($field instanceof FieldInterface) {
                continue;
            }

            $this->_fields[$fieldKey] = $fieldsService->createField($field);
        }

        $this->_fieldsByHandle = null;

        return $this->_fields;
    }

}
