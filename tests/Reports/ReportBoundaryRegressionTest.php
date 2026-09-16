<?php

declare(strict_types=1);

use craft\helpers\Db;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\ReportsController;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\models\ScheduledReport;
use yii\web\NotFoundHttpException;

$reportBoundaryIdentity = null;
$reportBoundarySite = null;
beforeEach(function () use (&$reportBoundaryIdentity, &$reportBoundarySite): void {
    $reportBoundaryIdentity = Craft::$app->getUser()->getIdentity();
    $reportBoundarySite = Craft::$app->getSites()->getCurrentSite();
});
afterEach(function () use (&$reportBoundaryIdentity, &$reportBoundarySite): void {
    Craft::$app->getUser()->setIdentity($reportBoundaryIdentity);
    Craft::$app->getSites()->setCurrentSite($reportBoundarySite);
});


it('intersects incremental exports with both saved report date bounds', function (string $since, array $expectedDates): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $ids = [];
    foreach (['2026-09-02', '2026-09-06', '2026-09-09', '2026-09-12'] as $day) {
        $submission = formie()->submission($form)->with(['message' => $day])->save();
        Db::update('{{%elements}}', ['dateCreated' => $day . ' 12:00:00'], ['id' => $submission->id]);
        $ids[$day] = (int)$submission->id;
    }
    $settings = new ReportSettings();
    $settings->filters = array_merge($settings->filters, [
        'formIds' => [$form->id],
        'startBound' => ['option' => 'date', 'date' => '2026-09-05 00:00:00'],
        'endBound' => ['option' => 'date', 'date' => '2026-09-10 23:59:59'],
    ]);
    $report = new Report(['name' => 'Bounded', 'handle' => 'bounded' . uniqid()]);
    $report->setSettingsModel($settings);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
    $scheduled = new ScheduledReport(['reportId' => $report->id, 'lastSentAt' => new DateTime($since)]);
    $query = Formie::$plugin->getReportScheduledDelivery()->buildSubmissionQuery($report, $scheduled);
    $expectedIds = array_map(fn($day) => $ids[$day], $expectedDates);
    expect(array_map('intval', $query->orderBy(['elements.id' => SORT_ASC])->ids()))->toBe($expectedIds);
    expect(Formie::$plugin->getReportQuery()->getSummaryCounts($report, null, new DateTime($since))['total'])->toBe(count($expectedIds));
    $method = new ReflectionMethod(Formie::$plugin->getReportExport(), '_buildQueryFromContext');
    $queued = $method->invoke(Formie::$plugin->getReportExport(), $report, ['since' => $since]);
    expect(array_map('intval', $queued->orderBy(['elements.id' => SORT_ASC])->ids()))->toBe($expectedIds);
})->with([
    'before saved start' => ['2026-09-01 00:00:00', ['2026-09-06', '2026-09-09']],
    'inside saved window' => ['2026-09-08 00:00:00', ['2026-09-09']],
    'after saved end' => ['2026-09-11 00:00:00', []],
]);

it('rejects reuse of a persisted single-use report download token', function (): void {
    $report = new Report(['name' => 'Private export', 'handle' => 'privateExport' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $files = Formie::$plugin->getReportExportFiles();
    $export = $files->createPending($report, 'csv', []);
    $path = tempnam(sys_get_temp_dir(), 'report-boundary-');
    file_put_contents($path, "message\nprivate-value\n");
    $export = $files->markReady($export, $path, 'private.csv', 'text/csv');
    $token = $export->downloadToken;
    Formie::$plugin->getSettings()->reportExportSingleUseDownload = true;
    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($export, $token): void {
            Craft::$app->getRequest()->setQueryParams(['uid' => $export->uid, 'downloadToken' => $token]);
            $controller = new ReportsController('reports', Formie::$plugin);
            ob_start(); // Craft closes one download buffer; preserve Pest's buffer.
            $response = $controller->actionDownloadExport();
            expect($response->getHeaders()->get('content-disposition'))->toContain('private.csv');
            $level = ob_get_level();
            ob_start();
            try {
                expect(fn() => $controller->actionDownloadExport())->toThrow(NotFoundHttpException::class);
            } finally {
                while (ob_get_level() > $level) { ob_end_clean(); }
            }
        });
        expect($files->getExportFileByToken($export->uid, $token))->toBeNull();
    } finally {
        if (is_file($path)) { unlink($path); }
    }
})->group('security');


it('returns no report submissions when none of the selected forms is authorized', function (): void {
    $form = formie()->form()->singleLineTextField('secret')->create();
    formie()->submission($form)->with(['secret' => 'Must not disclose'])->save();
    $settings = ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]], 'display' => ['fieldColumnsMode' => \verbb\formie\services\ReportColumns::FIELD_COLUMNS_MODE_SELECTED], 'columns' => [['type' => 'field', 'handle' => 'secret', 'label' => 'Secret', 'enabled' => true]]]);
    $report = new Report(['name' => 'Restricted', 'handle' => 'restricted' . uniqid()]);
    $report->setSettingsModel($settings);
    $denied = new \craft\elements\User();
    $service = Formie::$plugin->getReportQuery();
    expect($service->resolveFormIds([$form->id], $denied))->toBe([]);
    expect($service->buildSubmissionQuery($report, $denied)->ids())->toBe([]);
    expect($service->getTableData($report, user: $denied)['rows'])->toBe([]);
})->group('security');

it('runs queued interactive exports with the requesting users form permissions', function (): void {
    $form = formie()->form()->singleLineTextField('secret')->create();
    formie()->submission($form)->with(['secret' => 'Must not disclose'])->save();
    $user = new \craft\elements\User(['username' => 'reportRequester' . uniqid(), 'email' => uniqid() . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', \verbb\formie\services\Permissions::PERM_ACCESS_REPORTS, \verbb\formie\services\Permissions::PERM_MANAGE_REPORTS, \verbb\formie\services\Permissions::PERM_EXPORT_SUBMISSIONS]);
    $settings = ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]], 'display' => ['fieldColumnsMode' => \verbb\formie\services\ReportColumns::FIELD_COLUMNS_MODE_SELECTED], 'columns' => [['type' => 'field', 'handle' => 'secret', 'label' => 'Secret', 'enabled' => true]]]);
    $report = new Report(['name' => 'Queued restricted', 'handle' => 'queuedRestricted' . uniqid()]);
    $report->setSettingsModel($settings);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $export = Formie::$plugin->getReportExportFiles()->createPending($report, 'csv', [], user: $user);
    // Queue execution may have an administrator identity; it must not grant that identity to the requester.
    Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
    $result = Formie::$plugin->getReportExport()->runQueuedExport($export);
    try {
        expect(file_get_contents($result['path']))->not->toContain('Must not disclose');
        expect(file($result['path'], FILE_IGNORE_NEW_LINES))->toHaveCount(1);
    } finally { unlink($result['path']); }
})->group('security');


it('allows only one persisted claim from two copies of the same valid export token', function (): void {
    $report = new Report(['name' => 'Atomic export', 'handle' => 'atomicExport' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $files = Formie::$plugin->getReportExportFiles();
    $export = $files->createPending($report, 'csv', []);
    $path = tempnam(sys_get_temp_dir(), 'atomic-export-');
    file_put_contents($path, 'Private synthetic content');
    try {
        $export = $files->markReady($export, $path, 'private.csv', 'text/csv');
        $first = $files->getExportFileByToken($export->uid, $export->downloadToken);
        $second = $files->getExportFileByToken($export->uid, $export->downloadToken);
        expect($files->markConsumed($first))->toBeTrue();
        expect($files->markConsumed($second))->toBeFalse();
        expect($files->getExportFileById($export->id)->isTokenDownloadable())->toBeFalse();
        expect($files->getExportFileById($export->id)->isDownloadable())->toBeTrue();
    } finally { unlink($path); }
})->group('security');

it('keeps scheduled exports in their saved form scope without a logged-in identity', function (): void {
    $allowed = formie()->form()->singleLineTextField('message')->create();
    $unrelated = formie()->form()->singleLineTextField('message')->create();
    $row = formie()->submission($allowed)->with(['message' => 'Scheduled row'])->save();
    formie()->submission($unrelated)->with(['message' => 'Unrelated row'])->save();
    $report = new Report(['name' => 'Console scope', 'handle' => 'consoleScope' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$allowed->id]], 'display' => ['fieldColumnsMode' => \verbb\formie\services\ReportColumns::FIELD_COLUMNS_MODE_SELECTED], 'columns' => [['type' => 'field', 'handle' => 'message', 'label' => 'Message', 'enabled' => true]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    Craft::$app->getUser()->setIdentity(null);
    $schedule = new ScheduledReport(['reportId' => $report->id]);
    expect(array_map('intval', Formie::$plugin->getReportScheduledDelivery()->buildSubmissionQuery($report, $schedule)->ids()))->toBe([(int)$row->id]);
    $export = new \verbb\formie\models\ReportExportFile(['reportId' => $report->id, 'format' => 'csv', 'source' => \verbb\formie\models\ReportExportFile::SOURCE_SCHEDULED]);
    $result = Formie::$plugin->getReportExport()->runQueuedExport($export);
    try {
        expect(array_map('str_getcsv', file($result['path'], FILE_IGNORE_NEW_LINES)))->toBe([["\xEF\xBB\xBFMessage"], ['Scheduled row']]);
    } finally { unlink($result['path']); }
    $export->source = \verbb\formie\models\ReportExportFile::SOURCE_INTERACTIVE;
    expect(fn() => Formie::$plugin->getReportExport()->runQueuedExport($export))->toThrow(RuntimeException::class, 'permission');
})->group('security');
