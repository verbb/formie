<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\ReportsController;
use verbb\formie\models\{Report, ReportSettings};
use verbb\formie\services\Permissions;
use yii\web\{ForbiddenHttpException, NotFoundHttpException};

it('uses the report viewer export permission for immediate and queued downloads', function (string $grant, bool $allowed): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->with(['message' => 'Exported value'])->save();
    $report = new Report(['name' => 'Manager export', 'handle' => 'managerExport' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $name = 'manager' . uniqid();
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $grants = ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_REPORTS, Permissions::PERM_ACCESS_SUBMISSIONS, Permissions::PERM_VIEW_SUBMISSIONS];
    if ($grant !== '') { $grants[] = $grant; }
    if ($grant === Permissions::PERM_EXPORT_SUBMISSIONS) { $grants[] = Permissions::PERM_MANAGE_REPORTS; }
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();
    $files = Formie::$plugin->getReportExportFiles();
    $export = $files->createPending($report, 'csv', [], user: $user);
    $path = tempnam(sys_get_temp_dir(), 'manager-export-');
    file_put_contents($path, "message\nExported value\n");
    $export = $files->markReady($export, $path, 'manager.csv', 'text/csv');
    $originalIdentity = Craft::$app->getUser()->getIdentity();
    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $report, $export, $allowed): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->one());
            expect(Formie::$plugin->getPermissions()->canExportSubmissions(Craft::$app->getUser()->getIdentity()))->toBe($allowed);
            $controller = new ReportsController('reports', Formie::$plugin);
            if (!$allowed) {
                expect(fn() => $controller->actionExport($report->id))->toThrow(ForbiddenHttpException::class);
                expect(fn() => $controller->actionExportStatus($export->uid))->toThrow(ForbiddenHttpException::class);
                expect(fn() => $controller->actionDownloadQueuedExport($export->uid))->toThrow(ForbiddenHttpException::class);
                return;
            }
            expect($controller->actionExportStatus($export->uid)->data['status'])->toBe('ready');
            ob_start();
            $response = $controller->actionExport($report->id);
            expect($response->getHeaders()->get('content-disposition'))->toContain('.csv');
            fclose($response->stream[0]);
            $response->stream = null;
            $response->trigger(\craft\web\Response::EVENT_AFTER_SEND);
            $response->clear();
            ob_start();
            $response = $controller->actionDownloadQueuedExport($export->uid);
            expect($response->getHeaders()->get('content-disposition'))->toContain('manager.csv');
            fclose($response->stream[0]);
            $response->stream = null;
            // The same grant must not allow downloading another user's export.
            $other = new User(['username' => 'other' . uniqid(), 'email' => 'other' . uniqid() . '@example.test']);
            expect(Craft::$app->getElements()->saveElement($other))->toBeTrue();
            expect(Craft::$app->getUserPermissions()->saveUserPermissions($other->id, ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_REPORTS, Permissions::PERM_MANAGE_REPORTS]))->toBeTrue();
            Craft::$app->getUser()->setIdentity(User::find()->id($other->id)->one());
            expect(Formie::$plugin->getPermissions()->canExportSubmissions(Craft::$app->getUser()->getIdentity()))->toBeTrue();
            expect(fn() => $controller->actionExportStatus($export->uid))->toThrow(NotFoundHttpException::class);
            expect(fn() => $controller->actionDownloadQueuedExport($export->uid))->toThrow(NotFoundHttpException::class);
        });
    } finally {
        Craft::$app->getUser()->setIdentity($originalIdentity);
        if (is_file($export->filePath)) { unlink($export->filePath); }
        if (is_file($path)) { unlink($path); }
    }
})->with([
    'report manager' => [Permissions::PERM_MANAGE_REPORTS, true],
    'manager with export grant' => [Permissions::PERM_EXPORT_SUBMISSIONS, true],
    'report viewer' => ['', false],
]);
