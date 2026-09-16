<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\services\Permissions;

use Craft;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;

class SettingsAccessController extends Controller
{
    // Properties
    // =========================================================================

    protected ?string $settingsPage = null;

    private const SAVE_ACTION_PAGES = [
        'save-field-palette' => 'fields',
        'save-defaults' => 'defaults',
    ];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $permissions = Formie::$plugin->getPermissions();
        $user = Craft::$app->getUser()->getIdentity();
        $page = $this->settingsPage ?? $this->_resolveSettingsPage($action->id);

        $canAccess = $this->id === 'settings' && $action->id === 'index'
            ? $permissions->canAccessAnySettings($user)
            : $permissions->canAccessSettingsPage($user, $page);

        if (!$canAccess) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _resolveSettingsPage(string $actionId): string
    {
        if ($this->id === 'migrations') {
            return 'migrate/' . $actionId;
        }

        if ($this->id !== 'settings') {
            return $this->id;
        }

        if (isset(self::SAVE_ACTION_PAGES[$actionId])) {
            return self::SAVE_ACTION_PAGES[$actionId];
        }

        if ($actionId === 'save-settings') {
            return Formie::$plugin->getPermissions()->normalizeSettingsPage(
                (string)$this->request->getBodyParam('page', 'general'),
            );
        }

        return $actionId === 'index' ? 'general' : $actionId;
    }
}
