<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SettingsController;
use verbb\formie\Formie;
use yii\web\ForbiddenHttpException;

it('limits settings saves to the authorized page', function (array $settings, bool $allowed, bool $allSettings = false): void {
    $name = 'settings' . bin2hex(random_bytes(8));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', 'formie-settingsForms',
        ...($allSettings ? ['formie-accessSettings'] : []),
    ]))->toBeTrue();
    $projectConfig = Craft::$app->getProjectConfig();
    $original = Formie::$plugin->getSettings()->toArray();
    $originalConfig = $projectConfig->get('plugins.formie.settings');
    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($user, $settings, $allowed, $projectConfig, $originalConfig): void {
            Craft::$app->set('projectConfig', $projectConfig);
            $request = new \craft\web\Request([
                'pathInfo' => 'formie/settings/save-settings',
                'isCpRequest' => true,
                'isConsoleRequest' => false,
                'cookieValidationKey' => 'settings-page-save',
            ]);
            Craft::$app->set('request', $request);
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
            expect(Craft::$app->getUser()->checkPermission('formie-settingsForms'))->toBeTrue()
                ->and(Craft::$app->getUser()->checkPermission('formie-settingsSubmissions'))->toBeFalse();
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'page' => 'forms',
                'settings' => $settings,
            ]);
            $denied = false;
            try {
                (new SettingsController('settings', Formie::$plugin))->runAction('save-settings');
            } catch (ForbiddenHttpException) {
                $denied = true;
            }
            expect($denied)->toBe(!$allowed);
            if ($allowed) {
                expect($projectConfig->get('plugins.formie.settings.ajaxTimeout'))->toBe(12345);
            } else {
                expect($projectConfig->get('plugins.formie.settings'))->toBe($originalConfig);
            }
        }, ['method' => 'POST']);
    } finally {
        Formie::$plugin->getSettings()->setAttributes($original, false);
        $projectConfig->set('plugins.formie.settings', $originalConfig);
    }
})->with([
    'own page' => [['ajaxTimeout' => 12345], true],
    'another page' => [['ajaxTimeout' => 12345, 'maxIncompleteSubmissionAge' => 60], false],
    'unknown attribute' => [['ajaxTimeout' => 12345, 'unrecognizedSetting' => true], false],
    'existing all-settings role' => [['ajaxTimeout' => 12345, 'maxIncompleteSubmissionAge' => 60], true, true],
]);

it('authorizes direct settings actions by their controller', function (string $controllerClass, string $controllerId, string $page): void {
    $name = 'settingsAction' . bin2hex(random_bytes(8));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $permission = Formie::$plugin->getPermissions()->settingsPagePermissionKey($page);
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', $permission]))->toBeTrue();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $controllerClass, $controllerId, $page): void {
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setIsCpRequest(true);
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);
        expect(Formie::$plugin->getPermissions()->canAccessSettingsPage(Craft::$app->getUser()->getIdentity(), $page))->toBeTrue();
        $controller = new $controllerClass($controllerId, Formie::$plugin);
        expect($controller->beforeAction(new \yii\base\Action('index', $controller)))->toBeTrue();
        $general = new SettingsController('settings', Formie::$plugin);
        expect(fn() => $general->beforeAction(new \yii\base\Action('index', $general)))->toThrow(ForbiddenHttpException::class);
    }, ['method' => 'POST']);
})->with([
    [\verbb\formie\controllers\FormGroupsController::class, 'form-groups', 'form-groups'],
    [\verbb\formie\controllers\FormStatusesController::class, 'form-statuses', 'form-statuses'],
    [\verbb\formie\controllers\SubmissionStatusesController::class, 'submission-statuses', 'submission-statuses'],
    [\verbb\formie\controllers\ScheduledReportsController::class, 'scheduled-reports', 'scheduled-reports'],
]);
