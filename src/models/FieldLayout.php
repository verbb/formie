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
    private ?array $_cachedRows = null;
    private ?array $_cachedFields = null;
    private ?array $_fieldsByHandle = null;
    private ?array $_fieldsById = null;


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
        $this->_resetIndexes();

        foreach ($pages as $page) {
            $this->_pages[] = (!($page instanceof FieldLayoutPage)) ? new FieldLayoutPage($page) : $page;
        }
    }

    public function getRows(bool $includeDisabled = true): array
    {
        if ($includeDisabled) {
            if ($this->_cachedRows === null) {
                $rows = [];

                foreach ($this->getPages() as $page) {
                    foreach ($page->getRows() as $row) {
                        $rows[] = $row;
                    }
                }

                $this->_cachedRows = $rows;
            }

            return $this->_cachedRows;
        }

        return array_values(array_filter($this->getRows(), static function(FieldLayoutRow $row): bool {
            return (bool)$row->getFields(false);
        }));
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

            if ($field->isConditionallyHidden($element)) {
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

    private function _resetIndexes(): void
    {
        $this->_cachedRows = null;
        $this->_cachedFields = null;
        $this->_fieldsByHandle = null;
        $this->_fieldsById = null;
    }

    private function _getFieldsByHandle(): array
    {
        if ($this->_fieldsByHandle === null) {
            $this->_fieldsByHandle = [];

            // Layout lookups are hit repeatedly by forms, rendering, validation and submission access.
            // Cache the flattened field graph once so repeated handle lookups do not keep traversing
            // the same page -> row -> field structure on a single request.
            foreach ($this->getFields() as $field) {
                $this->_fieldsByHandle[$field->handle] = $field;
            }
        }

        return $this->_fieldsByHandle;
    }

    private function _getFieldsById(): array
    {
        if ($this->_fieldsById === null) {
            $this->_fieldsById = [];

            foreach ($this->getFields() as $field) {
                $this->_fieldsById[$field->id] = $field;
            }
        }

        return $this->_fieldsById;
    }

}
