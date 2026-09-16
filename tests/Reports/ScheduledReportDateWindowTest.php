<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\{Report, ReportSettings, ScheduledReport, ScheduledReportDelivery};

it('includes the selected schedule end date while preserving explicit timestamp cutoffs', function (string $endAt, string $now, bool $expected): void {
    $report = new Report(['name' => 'Date window', 'handle' => 'dateWindow' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $schedule = new ScheduledReport(['name' => 'Date window ' . uniqid(), 'reportId' => $report->id]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray([
        'frequency' => 'daily', 'hour' => 8, 'startAt' => '2026-09-17', 'endAt' => $endAt,
        'recipients' => ['fixture@example.test'],
    ]));
    $service = Formie::$plugin->getScheduledReports();
    expect($service->saveScheduledReport($schedule))->toBeTrue();
    $loaded = $service->getScheduledReportById($schedule->id);
    expect($loaded->getDeliveryModel()->endAt)->toBe($endAt);
    expect($service->isDue($loaded, new DateTime($now)))->toBe($expected);
})->with([
    'before start day' => ['2026-09-17', '2026-09-16 08:00:00', false],
    'before scheduled hour' => ['2026-09-17', '2026-09-17 07:59:59', false],
    'end day delivery' => ['2026-09-17', '2026-09-17 08:00:00', true],
    'end day final second' => ['2026-09-17', '2026-09-17 23:59:59', true],
    'following day' => ['2026-09-17', '2026-09-18 08:00:00', false],
    'before explicit cutoff' => ['2026-09-17 12:00:00', '2026-09-17 08:00:00', true],
    'at explicit cutoff' => ['2026-09-17 12:00:00', '2026-09-17 12:00:00', true],
    'after explicit cutoff' => ['2026-09-17 12:00:00', '2026-09-17 12:00:01', false],
]);
