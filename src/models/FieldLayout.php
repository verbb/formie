<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutElement;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout as CraftFieldLayout;

class FieldLayout extends CraftFieldLayout
{
    // Properties
    // =========================================================================

    private ?array $_pages = null;
    private ?array $_fields = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getCustomFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        if (!$this->id) {
            return [];
        }

        return $this->_fields = Formie::$plugin->getFields()->getFieldsByLayoutId($this->id);
    }

    public function setCustomFields(array $fields = null): void
    {
        $this->_fields = $fields;
    }

    public function getVisibleCustomFieldElements(ElementInterface $element): array
    {
        $elements = [];

        // When using `setFieldSettings()` settings are only applied for `getCustomFields()` and not
        // for individual field layout elements. Fetch them here so we update shortly.
        $customFields = ArrayHelper::index($this->getCustomFields(), 'handle');

        foreach ($this->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                if ($layoutElement instanceof CustomField) {
                    $isVisible = true;

                    // Update the custom field for this field layout element, with any overridden settings applied
                    $field = $layoutElement->getField();
                    $customField = ArrayHelper::getValue($customFields, $field->handle, $field);
                    $layoutElement->setField($customField);
                    $field = $layoutElement->getField();

                    // Check if this is a conditionally-hidden field
                    if ($field->isConditionallyHidden($element)) {
                        $isVisible = false;
                    }

                    // Check when we're doing a submission from the front-end, and we choose to validate the current page only
                    // Remove any custom fields that aren't in the current page. These are added by default
                    if ($element instanceof Submission && $element->validateCurrentPageOnly) {
                        $currentPageFields = $element->getForm()->getCurrentPage()->getCustomFields();

                        // Organise fields, so they're easier to check against
                        $currentPageFieldHandles = ArrayHelper::getColumn($currentPageFields, 'handle');

                        if (!in_array($field->handle, $currentPageFieldHandles)) {
                            $isVisible = false;
                        }
                    }

                    if ($isVisible) {
                        $elements[] = $layoutElement;
                    }
                }
            }
        }

        return $elements;
    }

    /**
     * Returns the layout’s pages.
     *
     * @return FieldLayoutPage[] The layout’s pages.
     */
    public function getPages(): array
    {
        if ($this->_pages !== null) {
            return $this->_pages;
        }

        if (!$this->id) {
            return [];
        }

        return $this->_pages = Formie::$plugin->getFields()->getLayoutPagesById($this->id);
    }

    /**
     * Sets the layout’s pages.
     *
     * @param array|FieldLayoutPage[] $pages An array of the layout’s pages, which can either be FieldLayoutPage
     * objects or arrays defining the page’s attributes.
     */
    public function setPages(array $pages): void
    {
        $this->_pages = [];

        foreach ($pages as $page) {
            if (is_array($page)) {
                $page = new FieldLayoutPage($page);
            }

            $page->setLayout($this);
            $this->_pages[] = $page;
        }
    }

    /**
     * @inheritDoc
     */
    public function getTabs(): array
    {
        // Override `getTabs()` to refer to pages for convenience.
        return $this->getPages();
    }

    /**
     * @inheritDoc
     */
    public function setTabs($tabs): void
    {
        // Override `setPages()` to refer to pages for convenience.
        $this->setPages($tabs);
    }
}
