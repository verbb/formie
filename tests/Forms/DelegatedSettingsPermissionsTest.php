<?php

use craft\elements\User;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SettingsController;
use verbb\formie\Formie;
use verbb\formie\services\FieldPalette;
use yii\web\ForbiddenHttpException;

it('saves builder configuration only for administrators on an unlocked project', function(string $page, string $grant, bool $unlocked, bool $allowed, bool $admin = false): void {
    $name = 'delegatedSettings' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test', 'admin' => $admin]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', 'formie-accessSettingsSection', $grant])))->toBeTrue();
    $config = Craft::$app->getProjectConfig();
    $originalSettings = $config->get('plugins.formie.settings');
    $originalPalette = $config->get(FieldPalette::CONFIG_KEY);
    $settings = Formie::$plugin->getSettings();
    $originalValues = $settings->toArray();
    $key = $page === 'defaults' ? 'defaultLabelPosition' : 'allowHtmlFieldTwig';
    $value = $page === 'defaults' ? 'below-input' : !$settings->allowHtmlFieldTwig;

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $config, $page, $key, $value, $allowed, $unlocked, $originalSettings, $originalPalette): void {
            Craft::$app->set('projectConfig', $config);
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges = $unlocked;
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'settings' => $page === 'defaults' ? Json::encode([$key => $value, 'pluginName' => 'must-not-change']) : [$key => $value, 'pluginName' => 'must-not-change'],
                'palette' => Json::encode(Formie::$plugin->getFieldPalette()->getSavePayload()),
            ]);
            $response = null;
            try {
                $response = (new SettingsController('settings', Formie::$plugin))->runAction($page === 'defaults' ? 'save-defaults' : 'save-field-palette');
            } catch (ForbiddenHttpException $exception) {
                if ($allowed) {
                    throw $exception;
                }
            }
            if ($allowed) {
                expect($response?->statusCode)->toBe(302);
                expect($config->get('plugins.formie.settings.' . $key))->toBe($value);
                expect(Formie::$plugin->getSettings()->pluginName)->not->toBe('must-not-change');
            } else {
                expect($response)->toBeNull();
                expect($config->get('plugins.formie.settings'))->toBe($originalSettings);
                expect($config->get(FieldPalette::CONFIG_KEY))->toBe($originalPalette);
            }
        }, ['method' => 'POST']);
    } finally {
        $config->set('plugins.formie.settings', $originalSettings);
        $config->set(FieldPalette::CONFIG_KEY, $originalPalette);
        $settings->setAttributes($originalValues, false);
    }
})->with([
    'admin defaults' => ['defaults', '', true, true, true],
    'admin fields' => ['fields', '', true, true, true],
    'locked admin defaults' => ['defaults', '', false, false, true],
    'locked admin fields' => ['fields', '', false, false, true],
    'defaults manager' => ['defaults', 'formie-settingsDefaults', true, false],
    'field manager' => ['fields', 'formie-settingsFields', true, false],
    'all settings defaults' => ['defaults', 'formie-accessSettings', true, false],
    'all settings fields' => ['fields', 'formie-accessSettings', true, false],
    'defaults cannot manage fields' => ['fields', 'formie-settingsDefaults', true, false],
    'fields cannot manage defaults' => ['defaults', 'formie-settingsFields', true, false],
    'general cannot manage defaults' => ['defaults', 'formie-settingsGeneral', true, false],
    'general cannot manage fields' => ['fields', 'formie-settingsGeneral', true, false],
    'parent cannot manage defaults' => ['defaults', 'formie-accessSettingsSection', true, false],
    'parent cannot manage fields' => ['fields', 'formie-accessSettingsSection', true, false],
    'locked defaults' => ['defaults', 'formie-settingsDefaults', false, false],
    'locked fields' => ['fields', 'formie-settingsFields', false, false],
]);

it('rejects retired status permissions', function(string $grant, string $allowed, string $denied): void {
    $name = 'statusSettings' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents(['formie-accessSettingsSection', $grant]));
    $permissions = Formie::$plugin->getPermissions();
    expect($permissions->canAccessSettingsPage($user, $allowed))->toBeFalse();
    expect($permissions->canAccessSettingsPage($user, $denied))->toBeFalse();
})->with([
    ['formie-settingsFormStatuses', 'form-statuses', 'submission-statuses'],
    ['formie-settingsSubmissionStatuses', 'submission-statuses', 'form-statuses'],
    ['formie-settingsSubmissionStatuses', 'statuses', 'form-statuses'],
]);

it('retains action grants beneath their settings pages without unrelated management access', function(string $grant, string $page): void {
    $name = 'independentPermission' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents([$grant]));
    expect($user->can($grant))->toBeTrue();
    expect(Formie::$plugin->getPermissions()->canAccessSettingsPage($user, $page))->toBeTrue();
    expect($user->can('formie-manageReports'))->toBeFalse();
    expect($user->can('formie-accessForms'))->toBeFalse();
})->with([
    ['formie-testScheduledReports', 'scheduled-reports'],
    ['formie-importForms', 'import-export'],
    ['formie-exportForms', 'import-export'],
]);
