<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\ReportsController;
use verbb\formie\models\{Report, ReportSettings, ReportExportFile};
use verbb\formie\services\Permissions;
use yii\web\NotFoundHttpException;

it('authorizes persisted report downloads by owner or an unexpired single-use signed token', function (): void {
    $users = [];
    foreach (['owner', 'other'] as $role) {
        $name = $role . bin2hex(random_bytes(8));
        $user = new User(['username' => $name, 'email' => $name . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_REPORTS, Permissions::PERM_MANAGE_REPORTS, Permissions::PERM_EXPORT_SUBMISSIONS]))->toBeTrue();
        $users[$role] = $user;
    }
    $report = new Report(['name' => 'Private export', 'handle' => 'export' . bin2hex(random_bytes(8))]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'report-');
    file_put_contents($path, "Name\nPrivate synthetic value\n");
    $files = Formie::$plugin->getReportExportFiles();
    $export = new ReportExportFile(['reportId' => $report->id, 'userId' => $users['owner']->id,
        'source' => ReportExportFile::SOURCE_INTERACTIVE, 'format' => 'csv', 'dateExpires' => new DateTime('+1 hour')]);
    expect($files->saveExportFile($export))->toBeTrue();
    $files->markReady($export, $path, 'private.csv', 'text/csv');
    $token = $export->downloadToken;
    $originalSingleUse = Formie::$plugin->getSettings()->reportExportSingleUseDownload;
    Formie::$plugin->getSettings()->reportExportSingleUseDownload = true;
    $download = function (?User $user, string $action, array $params) {
        return WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $action, $params) {
            Craft::$app->getUser()->setIdentity($user ? User::find()->id($user->id)->status(null)->one() : null);
            $request->setIsCpRequest($user !== null);
            $request->setQueryParams($params);
            if ($user) {
                expect(Craft::$app->getUser()->checkPermission('accessCp'))->toBeTrue();
                expect(Craft::$app->getUser()->checkPermission(Permissions::PERM_EXPORT_SUBMISSIONS))->toBeTrue();
            }
            $level = ob_get_level();
            ob_start(); // Craft closes a download buffer; preserve the test runner's buffer.
            try {
                $response = (new ReportsController('reports', Formie::$plugin))->runAction($action, $params);
                [$stream, $begin, $end] = $response->stream;
                fseek($stream, $begin);
                try { return stream_get_contents($stream, $end - $begin + 1); } finally { fclose($stream); $response->stream = null; }
            } finally {
                while (ob_get_level() > $level) { ob_end_clean(); }
            }
        }, ['method' => 'GET']);
    };
    try {
        expect($download($users['owner'], 'download-queued-export', ['uid' => $export->uid]))->toBe("Name\nPrivate synthetic value\n");
        expect(fn() => $download($users['other'], 'download-queued-export', ['uid' => $export->uid]))->toThrow(NotFoundHttpException::class);
        expect($download($users['owner'], 'download-queued-export', ['uid' => $export->uid]))->toBe("Name\nPrivate synthetic value\n");
        expect(fn() => $download(null, 'download-export', ['uid' => $export->uid, 'downloadToken' => 'wrong']))->toThrow(NotFoundHttpException::class);
        expect($files->getExportFileById($export->id)->dateDownloaded)->toBeNull();
        $export->dateExpires = new DateTime('-1 second');
        expect($files->saveExportFile($export))->toBeTrue();
        expect(fn() => $download(null, 'download-export', ['uid' => $export->uid, 'downloadToken' => $token]))->toThrow(NotFoundHttpException::class);
        $export->dateExpires = new DateTime('+1 hour');
        expect($files->saveExportFile($export))->toBeTrue();
        expect($download(null, 'download-export', ['uid' => $export->uid, 'downloadToken' => $token]))->toBe("Name\nPrivate synthetic value\n");
        expect($files->getExportFileById($export->id)->dateDownloaded)->not->toBeNull();
        expect(fn() => $download(null, 'download-export', ['uid' => $export->uid, 'downloadToken' => $token]))->toThrow(NotFoundHttpException::class);
        expect($download($users['owner'], 'download-queued-export', ['uid' => $export->uid]))->toBe("Name\nPrivate synthetic value\n");
    } finally {
        Formie::$plugin->getSettings()->reportExportSingleUseDownload = $originalSingleUse;
        $files->deleteExportFile($export);
    }
})->group('security');
