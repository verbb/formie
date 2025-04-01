<?php
namespace verbb\formie\storage;

use verbb\formie\base\StorageInterface;
use verbb\formie\elements\Form;

class MemoryStorage implements StorageInterface
{
    // Properties
    // =========================================================================

    private array $_storage = [];


    // Public Methods
    // =========================================================================

    public function getCurrentPageId(Form $form): ?int
    {
        return $this->_storage[$form->id] ?? null;
    }

    public function setCurrentPageId(Form $form, int $pageId): void
    {
        $this->_storage[$form->id] = $pageId;
    }

    public function resetCurrentPageId(Form $form): void
    {
        unset($this->_storage[$form->id]);
    }
}