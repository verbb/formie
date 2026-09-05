<?php
namespace verbb\formie\controllers;

use Craft;

use yii\web\ForbiddenHttpException;

/**
 * Guests must only hit site routes for actions listed in `$allowAnonymous`.
 *
 * Craft can be tricked into classifying a request as a Control Panel request
 * (e.g. percent-encoded `/%61dmin/actions/...`). CP requests skip site-request
 * ownership checks and historically redirected via Craft's unsandboxed
 * object-template renderer — so anonymous FE actions must never run as CP.
 */
trait AnonymousSiteRequestGuardTrait
{
    // Protected Methods
    // =========================================================================

    protected function forbidGuestControlPanelAnonymousActions(string $actionId): void
    {
        if (!$this->_isAnonymousAllowedAction($actionId)) {
            return;
        }

        if (Craft::$app->getUser()->getIsGuest() && !$this->request->getIsSiteRequest()) {
            throw new ForbiddenHttpException('Anonymous submissions are only permitted through the site request.');
        }
    }

    private function _isAnonymousAllowedAction(string $actionId): bool
    {
        $allowAnonymous = $this->allowAnonymous ?? false;

        if ($allowAnonymous === true) {
            return true;
        }

        if (!is_array($allowAnonymous)) {
            return false;
        }

        // Map form: ['submit' => ALLOW_ANONYMOUS_LIVE, ...]
        if (array_key_exists($actionId, $allowAnonymous)) {
            return true;
        }

        // List form: ['load', 'page']
        return in_array($actionId, $allowAnonymous, true);
    }
}
