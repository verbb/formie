<?php
namespace verbb\formie\models;

use craft\models\FieldLayout as CraftFieldLayout;
use verbb\formie\Formie;

class FieldLayout extends CraftFieldLayout
{
    // Properties
    // =========================================================================

    private ?array $_pages = null;


    // Properties
    // =========================================================================

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
