<?php

declare(strict_types=1);

use craft\elements\User;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\services\UserPermissions;
use verbb\formie\Formie;
use verbb\formie\jobs\ExportReport;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;
use verbb\formie\services\Permissions;

function reportWorkerFixture(string $mode): array
{
    Craft::$app->set('userPermissions', new UserPermissions());
    $allowed = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('answer', ['label' => 'Answer'])->create();
    $restricted = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('privateAnswer', ['label' => 'Private answer'])->create();
    formie()->submission($allowed)->with(['answer' => 'Allowed value'])->save();
    formie()->submission($restricted)->with(['privateAnswer' => 'Restricted value'])->save();
    $name = 'reportWorker' . bin2hex(random_bytes(6));
    $owner = new User(['username' => $name, 'email' => $name . '@example.test', 'active' => true]);
    expect(Craft::$app->getElements()->saveElement($owner))->toBeTrue();
    $grants = ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_REPORTS, Permissions::PERM_MANAGE_REPORTS, Permissions::PERM_ACCESS_SUBMISSIONS, Permissions::PERM_VIEW_SUBMISSIONS . ':' . $allowed->uid];
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($owner->id, $grants))->toBeTrue();
    $owner = User::find()->id($owner->id)->one();
    $report = new Report(['name' => 'Worker columns', 'handle' => $name]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$allowed->id, $restricted->id]],
        'display' => ['fieldColumnsMode' => $mode],
        'columns' => [['type' => 'field', 'handle' => 'answer', 'label' => 'Answer', 'enabled' => true]],
    ]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    return [$report, $owner, $allowed, $restricted, $grants];
}

it('uses the queued export owner for automatic field columns as well as rows', function (string $mode, bool $adminWorker): void {
    $originalIdentity = Craft::$app->getUser()->getIdentity();
    $originalPermissions = Craft::$app->getUserPermissions();
    $path = null;
    try {
        [$report, $owner] = reportWorkerFixture($mode);
        $files = Formie::$plugin->getReportExportFiles();
        $export = $files->createPending($report, 'json', [], user: $owner);
        Craft::$app->getUser()->setIdentity($adminWorker ? User::find()->admin(true)->one() : null);
        (new ExportReport(['exportFileId' => $export->id]))->execute(Craft::$app->getQueue());
        $export = $files->getExportFileById($export->id);
        $path = $export->filePath;
        $rows = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        expect($export->status)->toBe('ready')
            ->and($rows)->toHaveCount(1)
            ->and(array_column($rows, 'Answer'))->toBe(['Allowed value'])
            ->and($rows[0])->not->toHaveKey('Private answer');
    } finally {
        Craft::$app->set('userPermissions', $originalPermissions);
        Craft::$app->getUser()->setIdentity($originalIdentity);
        if ($path && is_file($path)) { unlink($path); }
    }
})->with([
    'automatic fields without a session' => ['all', false],
    'selected fields without a session' => ['selected', false],
    'automatic fields with unrelated admin session' => ['all', true],
    'selected fields with unrelated admin session' => ['selected', true],
]);

it('retains automatic fields in scheduled deliveries without granting that scope to test sends', function (string $mode, bool $testSend): void {
    $originalIdentity = Craft::$app->getUser()->getIdentity();
    $originalMailer = Craft::$app->getMailer();
    $originalPermissions = Craft::$app->getUserPermissions();
    try {
        [$report, $owner] = reportWorkerFixture($mode);
        $schedule = new ScheduledReport(['name' => 'Worker delivery ' . bin2hex(random_bytes(6)), 'reportId' => $report->id]);
        $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['format' => 'json', 'recipients' => ['scheduled@example.test']]));
        expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeTrue();
        $mailer = new class(['messageClass' => Message::class]) extends Mailer {
            public array $rows = [];
            public array $recipients = [];
            public function send($message): bool
            {
                $email = $message->getSymfonyEmail();
                $body = $email->getAttachments()[0]->getBody();
                $this->rows = json_decode(is_resource($body) ? stream_get_contents($body) : $body, true, flags: JSON_THROW_ON_ERROR);
                $this->recipients = array_keys($message->getTo());
                return true;
            }
        };
        Craft::$app->set('mailer', $mailer);
        Craft::$app->getUser()->setIdentity($testSend ? $owner : null);
        expect(Formie::$plugin->getReportScheduledDelivery()->send($schedule, $testSend, $testSend ? $owner : null))->toBeTrue();
        expect($mailer->rows)->toHaveCount($testSend ? 1 : 2)
            ->and(array_column($mailer->rows, 'Answer'))->toContain('Allowed value')
            ->and($mailer->recipients)->toBe($testSend ? [$owner->email] : ['scheduled@example.test']);
        if ($mode === 'all') {
            if ($testSend) {
                expect($mailer->rows[0])->not->toHaveKey('Private answer');
            } else {
                expect(array_column($mailer->rows, 'Private answer'))->toContain('Restricted value');
            }
        }
    } finally {
        Craft::$app->set('userPermissions', $originalPermissions);
        Craft::$app->set('mailer', $originalMailer);
        Craft::$app->getUser()->setIdentity($originalIdentity);
    }
})->with([
    'automatic scheduled fields' => ['all', false],
    'selected scheduled fields' => ['selected', false],
    'automatic test fields' => ['all', true],
    'selected test fields' => ['selected', true],
]);

it('rechecks revoked owner capabilities before creating a queued export', function (bool $revokeExport): void {
    $originalIdentity = Craft::$app->getUser()->getIdentity();
    $originalPermissions = Craft::$app->getUserPermissions();
    $path = null;
    try {
        [$report, $owner, $allowed, , $grants] = reportWorkerFixture('all');
        $export = Formie::$plugin->getReportExportFiles()->createPending($report, 'json', [], user: $owner);
        $removed = $revokeExport ? Permissions::PERM_MANAGE_REPORTS : Permissions::PERM_VIEW_SUBMISSIONS . ':' . $allowed->uid;
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($owner->id, array_values(array_diff($grants, [$removed]))))->toBeTrue();
        Craft::$app->set('userPermissions', new UserPermissions());
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        if ($revokeExport) {
            expect(fn() => (new ExportReport(['exportFileId' => $export->id]))->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class, 'permission');
            $stored = Formie::$plugin->getReportExportFiles()->getExportFileById($export->id);
            expect($stored->status)->toBe('failed')->and($stored->filePath)->toBeNull();
        } else {
            (new ExportReport(['exportFileId' => $export->id]))->execute(Craft::$app->getQueue());
            $stored = Formie::$plugin->getReportExportFiles()->getExportFileById($export->id);
            $path = $stored->filePath;
            expect(json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR))->toBe([]);
        }
    } finally {
        Craft::$app->set('userPermissions', $originalPermissions);
        Craft::$app->getUser()->setIdentity($originalIdentity);
        if ($path && is_file($path)) { unlink($path); }
    }
})->with(['export permission removed' => [true], 'form view permission removed' => [false]]);

it('keeps the direct export fallback when every selected column is disabled', function (): void {
    $originalIdentity = Craft::$app->getUser()->getIdentity();
    $originalPermissions = Craft::$app->getUserPermissions();
    $paths = [];
    try {
        [$report, $owner] = reportWorkerFixture('selected');
        $settings = $report->getSettingsModel();
        $settings->columns[0]['enabled'] = false;
        $report->setSettingsModel($settings);
        expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
        Craft::$app->getUser()->setIdentity($owner);
        $direct = Formie::$plugin->getReportExport()->export($report, 'json');
        $paths[] = $direct['path'];
        $expected = json_decode(file_get_contents($direct['path']), true, flags: JSON_THROW_ON_ERROR);
        expect($expected)->toHaveCount(1)->and($expected[0])->toHaveKey('Title')->not->toHaveKey('Answer');
        $file = Formie::$plugin->getReportExportFiles()->createPending($report, 'json', [], user: $owner);
        Craft::$app->getUser()->setIdentity(null);
        $queued = Formie::$plugin->getReportExport()->runQueuedExport($file);
        $paths[] = $queued['path'];
        expect(json_decode(file_get_contents($queued['path']), true, flags: JSON_THROW_ON_ERROR))->toBe($expected);
    } finally {
        Craft::$app->set('userPermissions', $originalPermissions);
        Craft::$app->getUser()->setIdentity($originalIdentity);
        foreach ($paths as $path) { if (is_file($path)) { unlink($path); } }
    }
});
