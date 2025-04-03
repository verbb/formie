<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\NestedField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\SavableComponent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout as CraftFieldLayout;
use craft\models\FieldLayoutTab;

use DateTime;

class FieldLayout extends SavableComponent
{
    // Properties
    // =========================================================================

    public ?string $uid = null;
    public ?string $type = null;

    private array $_deletedItems = [];
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

    public function getRows(bool $includeDisabled = true): array
    {
        $rows = [];

        foreach ($this->getPages() as $page) {
            foreach ($page->getRows($includeDisabled) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function getFields(bool $includeDisabled = true): array
    {
        $fields = [];

        foreach ($this->getPages() as $page) {
            foreach ($page->getRows($includeDisabled) as $row) {
                foreach ($row->getFields($includeDisabled) as $field) {
                    $fields[] = $field;
                }
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
        Craft::$app->getDeprecator()->log(__METHOD__, 'Layout’s `getCustomFields()` method has been deprecated. Use `getFields()` instead.');

        return $this->getFields();
    }

    public function getDeletedItems(): array
    {
        return $this->_deletedItems;
    }

    public function setDeletedItems(array $deletedItems): void
    {
        $this->_deletedItems = $deletedItems;
    }

    public function getFieldLayout(): CraftFieldLayout
    {
        // TODO: remove - for legacy purposes
        $config = ['type' => Submission::class];

        foreach ($this->getPages() as $page) {
            $tab = [
                'name' => $page->label,
            ];

            foreach ($page->getFields() as $field) {
                $tab['elements'][] = [
                    'label' => $field->label,
                    'attribute' => $field->handle,
                    'type' => "craft\\fieldlayoutelements\\TextField",
                ];
            }

            $config['tabs'][] = $tab;
        }

        return CraftFieldLayout::createFromConfig($config);
    }

    public function getFormBuilderConfig(): array
    {
        return array_map(function($page) {
            return $page->getFormBuilderConfig();
        }, $this->getPages());
    }

    public function validatePages(): void
    {
        foreach ($this->getPages() as $page) {
            if (!$page->validate()) {
                $this->addError('pages', $page->getErrors());
            }
        }
    }

    public function getVisiblePageFields(ElementInterface $element): array
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
        if ($field instanceof NestedField) {
            foreach ($field->getFields() as $childField) {
                // Append the current field's handle to the prefix.
                $this->_collectErrorsRecursive($childField, $prefix . '.' . $field->handle, $errors);
            }
        }
    }
}
