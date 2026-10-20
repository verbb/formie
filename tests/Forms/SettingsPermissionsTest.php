<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\controllers\SettingsController;
use verbb\formie\services\Permissions;

it('builds stable settings page permission keys', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->settingsPagePermissionKey('general'))->toBe('formie-settingsGeneral')
        ->and($permissions->settingsPagePermissionKey('encryption'))->toBe('formie-settingsEncryption')
        ->and($permissions->settingsPagePermissionKey('import-export'))->toBe('formie-settingsImportExport')
        ->and($permissions->settingsPagePermissionKey('migrate/freeform4'))->toBe('formie-settingsMigrateFreeform4');
});

it('normalizes settings page handles from posted page params', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->normalizeSettingsPage('spam-protection'))->toBe('spam-protection')
        ->and($permissions->normalizeSettingsPage('spam'))->toBe('spam-protection')
        ->and($permissions->normalizeSettingsPage('captchas'))->toBe('spam-protection');
});

it('resolves settings pages from redirect urls', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/spam-protection'))
        ->toBe('spam-protection')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/spam'))
        ->toBe('spam')
        ->and($permissions->normalizeSettingsPage('spam'))->toBe('spam-protection')
        ->and($permissions->normalizeSettingsPage('captchas'))->toBe('spam-protection')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/migrate/freeform5'))
        ->toBe('migrate/freeform5')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/fields'))
        ->toBe('fields')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/encryption'))
        ->toBe('encryption');
});

it('registers a settings permission definition for every settings page', function (): void {
    $permissions = Formie::$plugin->getPermissions();
    $pages = array_keys($permissions->getSettingsPageDefinitions());
    $definitions = $permissions->getSettingsPermissionDefinitions();

    expect(count($definitions))->toBe(count($pages) + 2)
        ->and($definitions)->toHaveKey($permissions->settingsPagePermissionKey('spam'))
        ->and($definitions)->toHaveKey($permissions->settingsPagePermissionKey('captchas'));

    foreach ($pages as $page) {
        expect($definitions)->toHaveKey($permissions->settingsPagePermissionKey($page));
    }
});

it('renders the dedicated Encryption settings page', function (): void {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $response = (new SettingsController('settings', Formie::$plugin))->runAction('encryption');
        $template = $response->getBehavior('template');

        expect($response->statusCode)->toBe(200)
            ->and($template->template)->toBe('formie/settings/encryption')
            ->and($template->variables)->toHaveKeys(['encryptionStatus', 'hasEncryptedFields']);
    }, ['method' => 'GET', 'requestUri' => '/admin/formie/settings/encryption']);
});

it('authorizes a settings save using its posted page', function (string $page): void {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function ($request) use ($page): void {
        // Craft resolves its segment cache during construction, before setPathInfo can affect it.
        $request = new \craft\web\Request([
            'pathInfo' => 'formie/settings/save-settings',
            'isCpRequest' => true,
            'isConsoleRequest' => false,
            'cookieValidationKey' => 'settings-request-test',
        ]);
        Craft::$app->set('request', $request);
        expect($request->getSegments())->toBe(['formie', 'settings', 'save-settings']);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'page' => $page,
        ]);
        $controller = new \verbb\formie\controllers\SettingsController('settings', Formie::$plugin);

        expect($controller->beforeAction(new \yii\base\Action('save-settings', $controller)))->toBeTrue();
    }, ['method' => 'POST', 'requestUri' => '/admin/formie/settings/save-settings']);
})->with(['general', 'forms', 'spam']);
