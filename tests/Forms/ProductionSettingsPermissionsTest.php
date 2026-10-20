<?php

use craft\elements\User;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\SettingsController;
use yii\base\NotSupportedException;
use yii\web\ForbiddenHttpException;
use craft\db\Query;
use verbb\formie\helpers\Table;

function withProductionPermissions(array $grants, callable $callback, string $method = 'POST'): void {
    $transaction = Craft::$app->getDb()->beginTransaction();
    $config = Craft::$app->getProjectConfig();
    $readOnly = $config->readOnly;
    $settings = Formie::$plugin->getSettings()->toArray();
    try {
        $name = 'lockedAudit' . bin2hex(random_bytes(6));
        $user = new User(['username' => $name, 'email' => $name . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
        // Seed retired grants directly too, proving stale database assignments cannot
        // authorize configuration endpoints after the checkboxes have been removed.
        foreach (PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', ...$grants]) as $grant) {
            Craft::$app->getDb()->createCommand()->upsert(Table::USERPERMISSIONS, ['name' => strtolower($grant)], false)->execute();
            $id = (new Query())->select('id')->from(Table::USERPERMISSIONS)->where(['name' => strtolower($grant)])->scalar();
            Craft::$app->getDb()->createCommand()->upsert(Table::USERPERMISSIONS_USERS, ['permissionId' => $id, 'userId' => $user->id], false)->execute();
        }
        WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $config, $callback): void {
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
            expect(\craft\helpers\App::projectConfigConfig()['readOnly'])->toBeTrue();
            $config->readOnly = true;
            Craft::$app->set('projectConfig', $config);
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
            expect(Craft::$app->getUser()->getIdentity()->admin)->toBeFalse();
            $callback($request);
        }, ['method' => $method]);
    } finally {
        $config->readOnly = $readOnly;
        Formie::$plugin->getSettings()->setAttributes($settings, false);
        $transaction->rollBack();
    }
}

it('blocks delegated project settings writes in the locked environment', function(string $page, array $payload): void {
    withProductionPermissions([Formie::$plugin->getPermissions()->settingsPagePermissionKey($page)], function($request) use ($page, $payload): void {
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken(), 'page' => $page, 'settings' => $payload]);
        expect(fn() => (new SettingsController('settings', Formie::$plugin))->runAction('save-settings'))->toThrow(ForbiddenHttpException::class);
    });
})->with([
    ['general', ['pluginName' => 'Locked Audit']],
    ['forms', ['ajaxTimeout' => 1234321]],
    ['notifications', ['emptyValuePlaceholder' => 'Locked Audit']],
    ['sent-notifications', ['maxSentNotificationsAge' => 1234321]],
    ['submissions', ['maxIncompleteSubmissionAge' => 1234321]],
    ['integrations-settings', ['queuePriority' => 1234321]],
]);

it('blocks delegated settings CRUD through actual controller saves', function(string $page, string $class): void {
    withProductionPermissions([Formie::$plugin->getPermissions()->settingsPagePermissionKey($page)], function($request) use ($page, $class): void {
        Formie::$plugin->getSettings()->validateCustomTemplates = false;
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'name' => 'Locked Audit', 'handle' => 'lockedAudit' . bin2hex(random_bytes(5)),
            'color' => 'green', 'description' => 'Audit', 'template' => 'audit',
            'outputCssLocation' => 'head', 'outputJsLocation' => 'footer', 'filenameFormat' => 'audit',
        ]);
        expect(fn() => (new $class($page, Formie::$plugin))->runAction('save'))->toThrow(ForbiddenHttpException::class);
    });
})->with([
    ['form-groups', \verbb\formie\controllers\FormGroupsController::class],
    ['form-statuses', \verbb\formie\controllers\FormStatusesController::class],
    ['submission-statuses', \verbb\formie\controllers\SubmissionStatusesController::class],
    ['form-templates', \verbb\formie\controllers\FormTemplatesController::class],
    ['email-templates', \verbb\formie\controllers\EmailTemplatesController::class],
    ['pdf-templates', \verbb\formie\controllers\PdfTemplatesController::class],
]);

it('shows permitted read-only pages while hiding configuration in production', function(): void {
    withProductionPermissions(array_keys(Formie::$plugin->getPermissions()->getSettingsPermissionDefinitions()), function(): void {
        $nav = (new \verbb\formie\variables\Formie())->getSettingsNavItems();
        expect($nav)->toHaveKeys(['encryption', 'import-export', 'synced-fields', 'scheduled-reports', 'support']);
        foreach (['general', 'defaults', 'form-groups', 'spam-protection', 'fields', 'notifications'] as $page) {
            expect($nav)->not->toHaveKey($page);
        }
    }, 'GET');
});

it('allows direct reads of production pages', function(string $page, string $id, string $action, string $class): void {
    withProductionPermissions([Formie::$plugin->getPermissions()->settingsPagePermissionKey($page)], function() use ($id, $action, $class): void {
        expect((new $class($id, Formie::$plugin))->runAction($action)->statusCode)->toBe(200);
    }, 'GET');
})->with([
    ['encryption', 'settings', 'encryption', SettingsController::class],
    ['synced-fields', 'synced-fields', 'index', \verbb\formie\controllers\SyncedFieldsController::class],
    ['scheduled-reports', 'scheduled-reports', 'index', \verbb\formie\controllers\ScheduledReportsController::class],
    ['import-export', 'import-export', 'index', \verbb\formie\controllers\ImportExportController::class],
    ['support', 'support', 'index', \verbb\formie\controllers\SupportController::class],
]);

it('blocks creating scheduled reports despite the delegated management grant', function(): void {
    withProductionPermissions(['formie-manageScheduledReports'], function($request): void {
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(), 'name' => 'Locked Audit', 'enabled' => true, 'reportId' => 123,
            'delivery' => ['frequency' => 'weekly', 'weekday' => 1, 'hour' => 8, 'recipients' => ['audit@example.test']],
        ]);
        expect(fn() => (new \verbb\formie\controllers\ScheduledReportsController('scheduled-reports', Formie::$plugin))->runAction('save'))->toThrow(ForbiddenHttpException::class);
    });
});

it('imports under the lock only when no new project dependency is required', function(bool $missingGroup): void {
    $source = formie()->form(['title' => 'Production audit source'])->create();
    $payload = \verbb\formie\helpers\ImportExportHelper::generateFormExport($source);
    if ($missingGroup) {
        $payload['dependencies'][] = ['kind' => 'group', 'config' => [
            'uid' => \craft\helpers\StringHelper::UUID(), 'name' => 'Missing audit group',
            'handle' => 'missingAudit' . bin2hex(random_bytes(5)),
        ]];
    }
    withProductionPermissions(['formie-importForms'], function() use ($payload, $missingGroup): void {
        $run = fn() => \verbb\formie\helpers\ImportExportHelper::importForUser($payload, null, Craft::$app->getUser()->getIdentity());
        if ($missingGroup) {
            expect($run)->toThrow(NotSupportedException::class, 'read-only');
        } else {
            $result = $run();
            expect($result->form->id)->not->toBeNull();
            expect($result->form->getErrors())->toBeEmpty();
        }
    });
})->with([false, true]);


it('keeps retired settings grants out of the editor in every environment', function(bool $unlocked): void {
    WebRequestTestHelper::withWebRequestContext(function() use ($unlocked): void {
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = $unlocked;
        $definitions = Formie::$plugin->getPermissions()->getSettingsPermissionDefinitions();
        expect(array_keys($definitions))->toBe([
            'formie-settingsEncryption', 'formie-settingsImportExport', 'formie-settingsSyncedFields',
            'formie-settingsScheduledReports', 'formie-settingsSupport', 'formie-settingsMigrateFreeform4',
            'formie-settingsMigrateFreeform5', 'formie-settingsMigrateSproutForms',
        ]);
    });
})->with([true, false]);

it('rejects generic configuration saves through read-only page grants', function(string $page): void {
    withProductionPermissions([Formie::$plugin->getPermissions()->settingsPagePermissionKey($page)], function($request) use ($page): void {
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken(), 'page' => $page, 'settings' => []]);
        expect(fn() => (new SettingsController('settings', Formie::$plugin))->runAction('save-settings'))->toThrow(ForbiddenHttpException::class);
    });
})->with(['encryption', 'synced-fields', 'scheduled-reports', 'support', 'import-export']);


it('uses stencil management rather than settings access for notification previews', function(array $grants, string $exception): void {
    withProductionPermissions($grants, function($request) use ($exception): void {
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'isStencil' => true, 'handle' => 'missingPreview' . bin2hex(random_bytes(6)),
        ]);
        // An authorized request reaches stencil lookup; no email is sent or rendered.
        expect(fn() => (new \verbb\formie\controllers\EmailController('email', Formie::$plugin))->runAction('preview'))->toThrow($exception);
    });
})->with([
    'manager' => [['formie-accessStencils'], \yii\web\NotFoundHttpException::class],
    'section only' => [['formie-accessStencilsSection'], ForbiddenHttpException::class],
    'retired settings' => [['formie-accessSettings'], ForbiddenHttpException::class],
    'support' => [['formie-settingsSupport'], ForbiddenHttpException::class],
]);
