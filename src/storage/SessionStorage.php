<?php
namespace verbb\formie\storage;

use verbb\formie\base\StorageInterface;
use verbb\formie\elements\Form;

use Craft;
use craft\helpers\Session;

class SessionStorage implements StorageInterface
{
    // Public Methods
    // =========================================================================

    public function getCurrentPageId(Form $form): ?int
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return null;
        }

        return Session::get($this->_getSessionKey($form));
    }

    public function setCurrentPageId(Form $form, int $pageId): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return;
        }

        Session::set($this->_getSessionKey($form), $pageId);
    }

    public function resetCurrentPageId(Form $form): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Session::exists()) {
            return;
        }

        Session::remove($this->_getSessionKey($form));
    }


    // Private Methods
    // =========================================================================

    private function _getSessionKey(Form $form): string
    {
        $keys = ['formie', 'pageId', $form->id];

        return implode(':', array_filter($keys));
    }
}