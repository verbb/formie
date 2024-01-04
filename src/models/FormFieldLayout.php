<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\Model;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class FormFieldLayout extends FieldLayout
{
    // Properties
    // =========================================================================

    private array $_pages = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $config = [])
    {
        // Allow passing a JSON string (from the database) to create the model
        if (is_string($config)) {
            $config = Json::decodeIfJson($config);
        }

        // Otherwise, we should always set defaults on a form's field layout
        if (empty($config)) {
            $config = [
                'pages' => [
                    [
                        'label' => Craft::t('formie', 'Page 1'),
                        'settings' => [],
                        'rows' => [],
                    ],
                ],
            ];
        }

        // No longer in use in Vue, but handle Formie 2 upgrades
        unset($config['id']);

        parent::__construct($config);
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'pages';

        return $attributes;
    }

    public function getPages(): array
    {
        return $this->_pages;
    }

    public function setPages(array $pages): void
    {
        foreach ($pages as $page) {
            $this->_pages[] = (!($page instanceof FormPage)) ? new FormPage($page) : $page;
        }
    }

    public function getRows(): array
    {
        $rows = [];

        foreach ($this->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function getFields(): array
    {
        $fields = [];

        foreach ($this->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                foreach ($row->getFields() as $field) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    public function saveLayout(Form $form): void
    {
        $fieldsService = Craft::$app->getFields();
        $context = 'formie:' . $form->uid;

        foreach ($this->getFields() as $field) {
            // Setup fields for saving - but only for new fields, so we don't overwrite it (think synced fields)
            if (!$field->id) {
                $field->context = $context;
            }

            // Validation will have already checked if there are any field issues
            $fieldsService->saveField($field);
        }

        // We may need to delete some fields that no longer exist. Determine that from the current layout
        // Fetch the layout again, fresh from the database.
        if ($form->id) {
            // Ensure that we refresh the field cache, as we fetch a new form, but we don't want to overwrite the 
            // current field instance which may already have changes.
            $fieldsService->refreshFields();

            $oldForm = Formie::$plugin->getForms()->getFormById($form->id);
            $oldFields = $oldForm?->getFields() ?? [];
            $allFieldIds = ArrayHelper::getColumn($this->getFields(), 'id');

            foreach ($oldFields as $oldField) {
                if (!in_array($oldField->id, $allFieldIds)) {
                    // When deleting a synced field, don't delete the field
                    if (!$oldField->isSynced && $oldField->uid) {
                        $fieldsService->deleteField($oldField);
                    } else {
                        // The reference to the synced field will be removed, but check if it's now considered synced still
                        Formie::$plugin->getFields()->updateIsSynced($oldField);
                    }
                }
            }
        }
    }

    public function getSerializedConfig(): array
    {
        return [
            'pages' => array_map(function($page) {
                return $page->getSerializedConfig();
            }, $this->getPages()),
        ];
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

    public function getCustomFields(): array
    {
        // Compatibility with Craft Field Layout
        return $this->getFields();
    }

    public function getVisibleCustomFields(ElementInterface $element): array
    {
        // Compatibility with Craft Field Layout
        return array_filter($this->getCustomFields(), function($field) use ($element) {
            return !$field->isConditionallyHidden($element);
        });
    }

    public function getCustomFieldElements(): array
    {
        // Compatibility with Craft Field Layout
        $elements = [];

        foreach ($this->getFields() as $field) {
            $elements[] = $field->layoutElement;
        }

        return $elements;
    }

    public function getVisibleCustomFieldElements(ElementInterface $element): array
    {
        // Compatibility with Craft Field Layout
        $currentPageFields = $element->getForm()?->getCurrentPage()?->getFields() ?? [];

        // Organise fields, so they're easier to check against
        $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

        return array_filter($this->getCustomFieldElements(), function($layoutElement) use ($element, $currentPageFieldHandles) {
            // Check when we're doing a submission from the front-end, and we choose to validate the current page only
            if ($element instanceof Submission && $element->validateCurrentPageOnly) {
                if (!in_array($layoutElement->field->handle, $currentPageFieldHandles)) {
                    return false;
                }
            }

            if ($layoutElement->field->isConditionallyHidden($element)) {
                return false;
            }

            return true;
        });
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pages'], 'validatePages'];

        return $rules;
    }
}
