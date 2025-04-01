<?php
namespace verbb\formie\storage;

use verbb\formie\base\StorageInterface;
use verbb\formie\elements\Form;

use Craft;

class QueryStringStorage implements StorageInterface
{
    // Public Methods
    // =========================================================================

    public function getCurrentPageId(Form $form): ?int
    {
        return Craft::$app->getRequest()->getQueryParam('pageId');
    }

    public function setCurrentPageId(Form $form, int $pageId): void
    {
        // Cannot set query params programmatically in the same request
        // This is handled by the frontend sending the `pageId`
    }

    public function resetCurrentPageId(Form $form): void
    {
        // Query params are stateless; nothing to reset
    }
}