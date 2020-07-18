<?php
namespace verbb\formie\models;

use craft\models\FieldLayout as CraftFieldLayout;
use verbb\formie\Formie;

class FieldLayout extends CraftFieldLayout
{
    // Properties
    // =========================================================================

    private $_pages;
    private $_fields;


    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        if ($this->_fields !== null) {
            return $this->_fields;
        }

        if (!$this->id) {
            return [];
        }

        return $this->_fields = Formie::$plugin->getFields()->getFieldsByLayoutId($this->id);
    }

    /**
     * @inheritDoc
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;
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
    public function setPages($pages)
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
        return $this->getPages();
    }

    /**
     * @inheritDoc
     */
    public function setTabs($tabs)
    {
        $this->setPages($tabs);
    }
}
