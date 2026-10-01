<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ValidationHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\SavableComponent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;

use DateTime;

class FieldLayout extends SavableComponent
{
    // Properties
    // =========================================================================

    public ?string $uid = null;
    public ?string $type = null;

    private array $_pages = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $config = [])
    {
        // Otherwise, we should always set defaults on a form's field layout
        if (!isset($config['pages'])) {
            $config['pages'] = [
                [
                    'label' => Craft::t('formie', 'Page 1'),
                    'settings' => [],
                    'rows' => [],
                ],
            ];
        }

        parent::__construct($config);
    }

    public function getForm(): ?Form
    {
        if ($this->_form || !$this->layoutId) {
            return $this->_form;
        }

        return $this->_form = Formie::$plugin->getForms()->getFormByLayoutId($this->layoutId);
    }

    public function getPages(): array
    {
        return $this->_pages;
    }

    public function setPages(array $pages): void
    {
        $this->_pages = [];

        foreach ($pages as $page) {
            $this->_pages[] = (!($page instanceof FieldLayoutPage)) ? new FieldLayoutPage($page) : $page;
        }
    }

    public function getRows(): array
    {
        $rows = [];

        foreach ($this->getPages() as $page) {
            array_push($rows, ...$page->getRows());
        }

        return $rows;
    }

    public function getEnabledRows(): array
    {
        return array_values(array_filter($this->getRows(), static fn(FieldLayoutRow $row) => $row->getEnabledFields() !== []));
    }

    public function getFields(): array
    {
        $fields = [];

        foreach ($this->getRows() as $row) {
            array_push($fields, ...$row->getFields());
        }

        return $fields;
    }

    public function getEnabledFields(): array
    {
        return array_values(array_filter($this->getFields(), static fn(FieldInterface $field) => !$field->getIsDisabled()));
    }

    public function getFieldsRecursively(): array
    {
        return \verbb\formie\helpers\FieldTraversal::recursively($this->getFields());
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        return $this->_getFieldsByHandle()[$handle] ?? null;
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        return $this->_getFieldsById()[$id] ?? null;
    }

    public function getFormBuilderConfig(): array
    {
        return array_map(function($page) {
            return $page->getFormBuilderConfig();
        }, $this->getPages());
    }

    public function validatePages(): void
    {
        foreach ($this->getPages() as $pageKey => $page) {
            if (!$page->validate()) {
                ValidationHelper::addPrefixedErrors($this, $page->getErrors(), "pages.$pageKey");
            }
        }
    }

    public function getFieldsToValidate(ElementInterface $element): array
    {
        // Compatibility with Craft Field Layout
        $currentPageFields = $element->getForm()?->getCurrentPage()?->getFields() ?? [];

        // Organise fields, so they're easier to check against
        $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

        return array_filter($this->getFields(), function($field) use ($element, $currentPageFieldHandles) {
            // Check when we're doing a submission from the front-end, and we choose to validate the current page only
            if ($element instanceof Submission && $element->validateCurrentPageOnly) {
                if (!in_array($field->handle, $currentPageFieldHandles)) {
                    return false;
                }
            }

            if ($field->getIsDisabled()) {
                return false;
            }

            if (\verbb\formie\conditions\ConditionVisibility::unavailable($field, $element)) {
                return false;
            }

            return true;
        });
    }

    public function getErrorsTree(): array
    {
        $errors = [];

        // A slightly more verbose error function than `getErrors()` to specifically support nested layouts
        // e.g. ['pageHandle.fieldHandle.nestedFieldHandle.label' => ['Label cannot be blank']]
        foreach ($this->getPages() as $page) {
            foreach ($page->getFields() as $field) {
                $this->_collectErrorsRecursive($field, $page->handle, $errors);
            }
        }

        return $errors;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pages'], 'validatePages'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _collectErrorsRecursive($field, string $prefix, array &$errors): void
    {
        // Check for errors on the current field.
        if ($fieldErrors = $field->getErrors()) {
            foreach ($fieldErrors as $errorKey => $error) {
                // Skip errors that are already bubbled up (e.g. nested pages).
                if (str_contains($errorKey, 'pages.')) {
                    continue;
                }

                // Build the key based on the current prefix and the field's handle.
                $errors[$prefix . '.' . $field->handle . '.' . $errorKey] = $error;
            }
        }

        // If the field is a nested field, recurse through its children.
        if ($field instanceof ParentField) {
            foreach ($field->getFields() as $childField) {
                // Append the current field's handle to the prefix.
                $this->_collectErrorsRecursive($childField, $prefix . '.' . $field->handle, $errors);
            }
        }
    }

    private function _getFieldsByHandle(): array
    {
        $index = [];

        foreach ($this->getFields() as $field) {
            $index[$field->handle] = $field;
        }

        return $index;
    }

    private function _getFieldsById(): array
    {
        $index = [];

        foreach ($this->getFields() as $field) {
            $index[$field->id] = $field;
        }

        return $index;
    }

}
