<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\base\Model;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class FormFieldLayout extends FieldLayout
{
    // Properties
    // =========================================================================

    private array $_pages = [];


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // The type for the field layout is actually a Submission element, as the Form element just defines the field. 
        // It doesn't use it for content, which is required to hook up the correct field layout to the element.
        $this->type = Submission::class;

        parent::init();
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

        // Field layouts are two parts - the config layout for the Form, which are pages/rows/fields, and the FieldLayout which are tabs/fields.
        // We need both to ensure that the Submission, which this is attached to, acts properly according to other Craft elements.

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

        // Update the real field layout for tabs/fields, convert our custom field layout to native Craft one
        $this->id = $form->fieldLayoutId;

        // Prepare the tabs and fields based on the pages content, created in the form builder, required for the field layout
        $this->tabs = array_map(function($page) {
            // Find an existing tab, or create a new one (to retain the correct UID)
            $tab = ArrayHelper::firstWhere($this->getTabs(), 'name', $page->label) ?? new FieldLayoutTab();
            $tab->layout = $this;
            $tab->name = $page->label;

            $tab->elements = array_map(function($field) use ($tab) {
                // Find an existing tab field, or create a new one (to retain the correct UID)
                $layoutField = new CustomField($field);

                // If there's an existing field, we should include it's UID, so a new one isn't created
                if ($existingField = ArrayHelper::firstWhere($tab->elements, 'fieldUid', $field->uid)) {
                    $layoutField->uid = $existingField->uid;
                }

                // Ensure that we carry-over the required setting to the field layout, from the field
                $layoutField->required = (bool)$field->required;
                
                return $layoutField;
            }, $page->getFields());

            return $tab;
        }, $this->getPages());

        $fieldsService->saveLayout($this);

        // Update the form data, as we'll likely be saving the form after this
        $form->fieldLayoutId = $this->id;
    }

    public function getSerializedLayout(): array
    {
        $layout = $this->toArray(['pages']);

        // Convert full field models into just their UID which reference the fields table.
        // Otherwise, we would end up saving the field data to the layout config for the form.
        foreach ($layout['pages'] as $pageKey => &$page) {
            foreach ($page['rows'] as $rowKey => &$row) {
                foreach ($row['fields'] as $fieldKey => &$field) {
                    $field = ['fieldUid' => $field['uid']];
                }
            }
        }

        return $layout;
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


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pages'], 'validatePages'];

        return $rules;
    }
}
