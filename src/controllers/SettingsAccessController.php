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
        $page = $this->settingsPage ?? $this->_resolveSettingsPageFromRequest();

        if (!$permissions->canAccessSettingsPage($user, $page)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _resolveSettingsPageFromRequest(): string
    {
        $request = Craft::$app->getRequest();

        if ($request->getSegment(2) !== 'settings') {
            return 'general';
        }

        $section = $request->getSegment(3) ?: 'general';

        if (isset(self::SAVE_ACTION_PAGES[$section])) {
            return self::SAVE_ACTION_PAGES[$section];
        }

        if ($section === 'save-captchas') {
            return 'spam-protection';
        }

        if ($section === 'save-settings') {
            return $permissions->normalizeSettingsPage(
                (string)$request->getBodyParam('page', 'general'),
            );
        }

        if (in_array($section, ['spam', 'captchas'], true)) {
            return 'spam-protection';
        }

        if ($section === 'migrate') {
            $plugin = $request->getSegment(4);

            return $plugin ? "migrate/$plugin" : 'general';
        }

        return $section;
    }
}
