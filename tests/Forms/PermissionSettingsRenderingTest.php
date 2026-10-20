<?php

use craft\elements\User;
use craft\web\TemplateResponseFormatter;
use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\controllers\ScheduledReportsController;
use verbb\formie\controllers\SettingsController;
use verbb\formie\Formie;
use yii\base\Action;
use yii\web\ForbiddenHttpException;

it('renders one Select All control for all Formie permissions', function(): void {
    WebRequestTestHelper::withWebRequestContext(function($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = $view->renderTemplate('_includes/permissions', [
            'permissions' => Formie::$plugin->getPermissions()->getPermissionGroups(),
            'subject' => null,
        ]);
        expect($html)->toContain('Formie', 'View synced field usage', 'View scheduled reports');
        expect($html)->not->toContain('legacy permission', 'value="formie-settingsStatuses"', 'value="formie-settingsSpamProtection"');
        expect(substr_count($html, 'value="formie-settingsSubmissionStatuses"'))->toBe(0);
        expect(substr_count($html, 'value="formie-testScheduledReports"'))->toBe(1);
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        expect($xpath->query('//h3')->length)->toBe(1);
        expect($xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " select-all ")]')->length)->toBe(1);
        expect($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " user-permissions ")]')->length)->toBe(1);
        expect($xpath->query('//ul[@id="formie-accessSettingsSection-nested"]//input[@value="formie-settingsSyncedFields" and @disabled]')->length)->toBe(1);
        expect($xpath->query('//ul[@id="formie-accessSettingsSection-nested"]//input[@value="formie-accessSettings"]')->length)->toBe(0);
    });
});

it('renders settings controls to match the assigned capability', function(string $controllerClass, string $controllerId, string $action, string $grant, string $expected, ?string $absent): void {
    $name = 'permissionRendering' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', ...($controllerClass === SettingsController::class ? ['formie-accessSettingsSection'] : []), $grant]));
    WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $controllerClass, $controllerId, $action, $expected, $absent): void {
        $request->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $args = $controllerClass === ScheduledReportsController::class ? ['scheduledReport' => new \verbb\formie\models\ScheduledReport(['id' => 123, 'name' => 'Existing schedule'])] : [];
        $response = (new $controllerClass($controllerId, Formie::$plugin))->runAction($action, $args);
        (new TemplateResponseFormatter())->format($response);
        expect($response->statusCode)->toBe(200)->and($response->content)->toContain($expected);
        if ($absent !== null) {
            expect($response->content)->not->toContain($absent);
        }
    });
})->with([
    'view schedule' => [ScheduledReportsController::class, 'scheduled-reports', 'edit', 'formie-settingsScheduledReports', '<fieldset disabled>', 'Send Test Email'],
    'test schedule' => [ScheduledReportsController::class, 'scheduled-reports', 'edit', 'formie-testScheduledReports', 'Send Test Email', 'Save and continue editing'],
    'import forms' => [ImportExportController::class, 'import-export', 'index', 'formie-importForms', 'Review Import', null],
]);

it('separates scheduled report test sends from administrator-only mutations', function(string $actionId, bool $manager): void {
    $name = 'schedulePermission' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', $manager ? 'formie-testScheduledReports' : 'formie-settingsScheduledReports']));
    WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $actionId, $manager): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);
        $controller = new ScheduledReportsController('scheduled-reports', Formie::$plugin);
        $action = new Action($actionId, $controller);
        if ($manager && $actionId === 'test-send') {
            expect($controller->beforeAction($action))->toBeTrue();
        } else {
            expect(fn() => $controller->beforeAction($action))->toThrow(ForbiddenHttpException::class);
        }
    }, ['method' => 'POST']);
})->with(['save', 'delete', 'test-send'])->with([true, false]);


it('allows scheduled report edits only to administrators in editable environments', function(bool $admin, bool $unlocked, string $action): void {
    $name = 'scheduleConfig' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test', 'admin' => $admin]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', 'formie-settingsScheduledReports', 'formie-testScheduledReports']));
    WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $admin, $unlocked, $action): void {
        $request->setIsCpRequest(true);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = $unlocked;
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);
        $controller = new ScheduledReportsController('scheduled-reports', Formie::$plugin);
        if ($admin && $unlocked) {
            expect($controller->beforeAction(new Action($action, $controller)))->toBeTrue();
        } else {
            expect(fn() => $controller->runAction($action))->toThrow(ForbiddenHttpException::class);
        }
    }, ['method' => 'POST']);
})->with([true, false])->with([true, false])->with(['save', 'delete']);
