<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\{Report, ReportSettings, ScheduledReport, ScheduledReportDelivery, ReportExportFile};
use verbb\formie\services\ReportColumns;
use craft\helpers\Db;

it('keeps saved report bounds for incremental attachments and queued exports and marks only successful sends', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    foreach (['Before' => '2026-09-02', 'Inside' => '2026-09-07', 'After' => '2026-09-12'] as $name => $date) {
        $submission = formie()->submission($form)->with(['fullName' => $name])->save();
        Db::update('{{%elements}}', ['dateCreated' => $date . ' 12:00:00'], ['id' => $submission->id]);
    }
    $settings = ReportSettings::fromArray(['filters' => [
        'formIds' => [$form->id],
        'startBound' => ['option' => 'date', 'date' => '2026-09-05 00:00:00'],
        'endBound' => ['option' => 'date', 'date' => '2026-09-10 23:59:59'],
    ], 'display' => ['fieldColumnsMode' => ReportColumns::FIELD_COLUMNS_MODE_SELECTED],
        'columns' => [['type' => 'field', 'handle' => 'fullName', 'label' => 'Name', 'enabled' => true]]]);
    $report = new Report(['name' => 'Window contract', 'handle' => 'window' . bin2hex(random_bytes(8))]);
    $report->setSettingsModel($settings);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $schedule = new ScheduledReport(['name' => 'Window delivery ' . bin2hex(random_bytes(8)), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['format' => 'csv', 'recipients' => ['audit@example.test']]));
    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($schedule))->toBeTrue();
    Formie::$plugin->getScheduledReports()->markSent($schedule, new DateTime('2026-09-01 00:00:00'));
    $service = Formie::$plugin->getReportScheduledDelivery();
    $originalMailer = Craft::$app->getMailer();
    $originalIdentity = Craft::$app->getUser()->getIdentity();
    $mailer = new class extends \craft\mail\Mailer {
        public bool $accept = false;
        public array $messages = [];
        public function send($message): bool {
            $email = $message->getSymfonyEmail();
            $body = $email->getAttachments()[0]->getBody();
            $this->messages[] = ['to' => array_keys($message->getTo()), 'csv' => is_resource($body) ? stream_get_contents($body) : $body, 'html' => $email->getHtmlBody()];
            return $this->accept;
        }
    };
    Craft::$app->set('mailer', $mailer);
    Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
    Craft::$app->getView()->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
    try {
        expect(array_map(fn($row) => $row->getFieldValue('fullName'), $service->buildSubmissionQuery($report, $schedule)->all()))->toBe(['Inside']);
        $summary = Formie::$plugin->getReportQuery()->getSummaryCounts($report, null, $schedule->lastSentAt);
        expect($summary['total'])->toBe(1);
        $export = Formie::$plugin->getReportExport()->runQueuedExport(new ReportExportFile([
            'reportId' => $report->id, 'format' => 'csv', 'source' => ReportExportFile::SOURCE_SCHEDULED,
            'context' => Formie::$plugin->getReportExport()->buildExportContext([], null, '2026-09-01 00:00:00'),
        ]));
        try {
            expect(array_map('str_getcsv', file($export['path'], FILE_IGNORE_NEW_LINES)))->toBe([["\xEF\xBB\xBFName"], ['Inside']]);
        } finally { unlink($export['path']); }
        expect(fn() => $service->send($schedule))->toThrow(RuntimeException::class, 'Couldn’t send');
        $storedDate = fn() => (new \craft\db\Query())->select('lastSentAt')->from(\verbb\formie\helpers\Table::FORMIE_SCHEDULED_REPORTS)->where(['id' => $schedule->id])->scalar();
        expect($storedDate())->toBe('2026-09-01 00:00:00');
        $mailer->accept = true;
        expect($service->send($schedule, true))->toBeTrue();
        expect($storedDate())->toBe('2026-09-01 00:00:00');
        $sendStarted = time();
        expect($service->send($schedule))->toBeTrue();
        $sentAt = $storedDate();
        expect($sentAt)->toBeString()->not->toBe('');
        expect((new DateTimeImmutable($sentAt, new DateTimeZone('UTC')))->getTimestamp())
            ->toBeGreaterThanOrEqual($sendStarted)->toBeLessThanOrEqual(time());
        expect($mailer->messages)->toHaveCount(3);
        foreach ($mailer->messages as $message) {
            expect(array_map('str_getcsv', preg_split('/\r?\n/', trim($message['csv']))))->toBe([["\xEF\xBB\xBFName"], ['Inside']]);
            $document = new DOMDocument();
            $document->loadHTML($message['html']);
            expect((new DOMXPath($document))->evaluate('normalize-space(//tr[th[normalize-space(.)="Total submissions"]]/td)'))->toBe('1');
        }
        expect(array_column($mailer->messages, 'to'))->toBe([
            ['audit@example.test'], [Craft::$app->getUser()->getIdentity()->email], ['audit@example.test'],
        ]);
    } finally {
        Craft::$app->set('mailer', $originalMailer);
        Craft::$app->getUser()->setIdentity($originalIdentity);
    }
});

it('runs daily and weekly schedules only in their due window and once per period', function (): void {
    $service = Formie::$plugin->getScheduledReports();
    $schedule = new ScheduledReport();
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['frequency' => 'daily', 'hour' => 8, 'startAt' => '2026-09-07', 'endAt' => '2026-09-14']));
    expect($service->isDue($schedule, new DateTime('2026-09-06 09:00:00')))->toBeFalse();
    expect($service->isDue($schedule, new DateTime('2026-09-07 07:59:59')))->toBeFalse();
    expect($service->isDue($schedule, new DateTime('2026-09-07 08:00:00')))->toBeTrue();
    $schedule->lastSentAt = new DateTime('2026-09-07 08:10:00');
    expect($service->isDue($schedule, new DateTime('2026-09-07 09:00:00')))->toBeFalse();
    expect($service->isDue($schedule, new DateTime('2026-09-08 08:00:00')))->toBeTrue();
    expect($service->isDue($schedule, new DateTime('2026-09-15 09:00:00')))->toBeFalse();
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray(['frequency' => 'weekly', 'weekday' => 1, 'hour' => 8]));
    expect($service->isDue($schedule, new DateTime('2026-09-08 09:00:00')))->toBeFalse();
    expect($service->isDue($schedule, new DateTime('2026-09-07 09:00:00')))->toBeFalse();
    expect($service->isDue($schedule, new DateTime('2026-09-14 08:00:00')))->toBeTrue();
});
