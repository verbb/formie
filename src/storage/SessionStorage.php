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
        if (!$this->_canUseSession()) {
            return null;
        }

        return Session::get($this->_getSessionKey($form));
    }

    public function setCurrentPageId(Form $form, int $pageId): void
    {
        if (!$this->_canUseSession()) {
            return;
        }

        Session::set($this->_getSessionKey($form), $pageId);
    }

    public function resetCurrentPageId(Form $form): void
    {
        if (!$this->_canUseSession()) {
            return;
        }

        Session::remove($this->_getSessionKey($form));
    }


    // Private Methods
    // =========================================================================

    private function _canUseSession(): bool
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return false;
        }

        // Running the queue from a web request (and other late-phase code) may already have flushed headers.
        if (headers_sent()) {
            return false;
        }

        return Session::exists();
    }

    private function _getSessionKey(Form $form): string
    {
        $keys = ['formie', 'pageId', $form->id, $form->getSessionKey()];

        return implode(':', array_filter($keys));
    }
}