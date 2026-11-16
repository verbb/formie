<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use verbb\formie\Formie;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\models\ScheduledReport;
use verbb\formie\models\ScheduledReportDelivery;

// Report bounds are calendar days and times in the site's time zone, like Craft's own date filters.
// Submissions are stored in UTC, so each case places them either side of the site's day boundaries.

beforeEach(function(): void {
    $this->originalTimeZone = Craft::$app->getTimeZone();
    $this->originalIdentity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
});

afterEach(function(): void {
    DateTimeHelper::resume();
    Craft::$app->setTimeZone($this->originalTimeZone);
    Craft::$app->getUser()->setIdentity($this->originalIdentity);
});

function siteTimeZoneReportFixture(array $utcDates, array $filters): array
{
    $form = formie()->form()->singleLineTextField('message')->create();
    $ids = [];

    foreach ($utcDates as $key => $utcDate) {
        $submission = formie()->submission($form)->with(['message' => $key])->save();
        Db::update('{{%elements}}', ['dateCreated' => $utcDate], ['id' => $submission->id]);
        $ids[$key] = (int)$submission->id;
    }

    $settings = new ReportSettings();
    $settings->filters = array_merge($settings->filters, ['formIds' => [$form->id]], $filters);
    $report = new Report(['name' => 'Site time zone', 'handle' => 'siteTimeZone' . uniqid()]);
    $report->setSettingsModel($settings);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    return [Formie::$plugin->getReports()->getReportById((int)$report->id), $ids];
}

function siteTimeZoneReportResults(Report $report, array $ids, array $viewer = []): array
{
    $reportQuery = Formie::$plugin->getReportQuery();
    $matched = array_map('intval', $reportQuery->buildSubmissionQuery($report, null, $viewer)->orderBy(['elements.id' => SORT_ASC])->ids());
    $chart = $reportQuery->getChartData($report, null, $viewer);
    $chartDays = [];

    foreach ($chart['rows'] as $row) {
        if ($row['total'] > 0) {
            $chartDays[$row['date']] = $row['total'];
        }
    }

    return [
        'rows' => array_keys(array_intersect($ids, $matched)),
        'table' => $reportQuery->getTableData($report, 1, 100, null, null, $viewer)['pagination']['total'],
        'summary' => $reportQuery->getSummaryCounts($report, null, null, $viewer)['total'],
        'chart' => $chartDays,
    ];
}

$todayBound = ['option' => 'today', 'date' => null, 'offset' => 'add', 'offsetNumber' => 0, 'offsetType' => 'days'];

it('resolves Today to the site day and matches submissions made on it', function(string $timeZone, string $nowUtc, array $utcDates, string $siteDay, array $expected) use ($todayBound): void {
    Craft::$app->setTimeZone($timeZone);
    DateTimeHelper::pause(new DateTime($nowUtc, new DateTimeZone('UTC')));
    [$report, $ids] = siteTimeZoneReportFixture($utcDates, ['startBound' => $todayBound, 'endBound' => $todayBound]);

    $filters = Formie::$plugin->getReportQuery()->resolveFilters($report);

    expect($filters['startDate'])->toBe("$siteDay 00:00:00")
        ->and($filters['endDate'])->toBe("$siteDay 23:59:59")
        ->and(siteTimeZoneReportResults($report, $ids))->toBe([
            'rows' => $expected,
            'table' => count($expected),
            'summary' => count($expected),
            'chart' => [$siteDay => count($expected)],
        ]);
})->with([
    // 08:04 on 17 November in Melbourne is still 16 November in UTC.
    'east of UTC' => ['Australia/Melbourne', '2026-11-16 21:04:00', [
        'previous site day' => '2026-11-16 12:30:00',
        'site morning' => '2026-11-16 21:00:00',
        'site evening' => '2026-11-17 12:30:00',
        'site late evening' => '2026-11-17 12:45:00',
        'next site day' => '2026-11-17 13:30:00',
    ], '2026-11-17', ['site morning', 'site evening', 'site late evening']],
    // 22:00 on 16 November in New York is already 17 November in UTC.
    'west of UTC' => ['America/New_York', '2026-11-17 03:00:00', [
        'previous site day' => '2026-11-16 04:30:00',
        'site morning' => '2026-11-16 05:30:00',
        'site evening' => '2026-11-17 02:00:00',
        'site late evening' => '2026-11-17 03:30:00',
        'next site day' => '2026-11-17 05:30:00',
    ], '2026-11-16', ['site morning', 'site evening', 'site late evening']],
]);

it('keeps Specific Date/Time bounds in site time through resolving and repeated editor saves', function(): void {
    Craft::$app->setTimeZone('Australia/Melbourne');
    [$report, $ids] = siteTimeZoneReportFixture([
        'previous site day' => '2026-11-16 12:30:00',
        'site morning' => '2026-11-16 21:00:00',
        'next site day' => '2026-11-17 13:30:00',
    ], [
        'startBound' => ['option' => 'date', 'date' => '2026-11-17 00:00:00', 'offset' => 'add', 'offsetNumber' => 0, 'offsetType' => 'days'],
        'endBound' => ['option' => 'date', 'date' => '2026-11-17 23:59:59', 'offset' => 'add', 'offsetNumber' => 0, 'offsetType' => 'days'],
    ]);

    // The editor loads its values and posts them back unchanged, which must not move the dates.
    for ($i = 0; $i < 2; $i++) {
        $values = Formie::$plugin->getReportEditor()->getEditorValues($report);
        expect($values['filters']['startBound']['date'])->toBe('2026-11-17 00:00:00')
            ->and($values['filters']['endBound']['date'])->toBe('2026-11-17 23:59:59');
        Formie::$plugin->getReportEditor()->applyPayload($report, $values);
        expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
        $report = Formie::$plugin->getReports()->getReportById((int)$report->id);
    }

    $filters = Formie::$plugin->getReportQuery()->resolveFilters($report);

    expect($report->getSettingsModel()->filters['startBound']['date'])->toBe('2026-11-17 00:00:00')
        ->and($filters['startDate'])->toBe('2026-11-17 00:00:00')
        ->and($filters['endDate'])->toBe('2026-11-17 23:59:59')
        ->and(siteTimeZoneReportResults($report, $ids))->toBe([
            'rows' => ['site morning'],
            'table' => 1,
            'summary' => 1,
            'chart' => ['2026-11-17' => 1],
        ]);
});

it('reads viewer date ranges in site time', function(): void {
    Craft::$app->setTimeZone('America/New_York');
    $noBound = ['option' => '', 'date' => null, 'offset' => 'add', 'offsetNumber' => 0, 'offsetType' => 'days'];
    [$report, $ids] = siteTimeZoneReportFixture([
        'previous site day' => '2026-11-16 04:30:00',
        'site morning' => '2026-11-16 05:30:00',
        'next site day' => '2026-11-17 05:30:00',
    ], ['startBound' => $noBound, 'endBound' => $noBound]);

    expect(siteTimeZoneReportResults($report, $ids, ['startDate' => '2026-11-16 00:00:00', 'endDate' => '2026-11-16 23:59:59']))->toBe([
        'rows' => ['site morning'],
        'table' => 1,
        'summary' => 1,
        'chart' => ['2026-11-16' => 1],
    ]);
});

it('counts submissions since the last scheduled delivery in site time', function(): void {
    Craft::$app->setTimeZone('Australia/Melbourne');
    [$report, $ids] = siteTimeZoneReportFixture([
        'before last send' => '2026-11-16 19:00:00',
        'after last send' => '2026-11-16 21:00:00',
    ], ['startBound' => ['option' => '', 'date' => null], 'endBound' => ['option' => '', 'date' => null]]);
    // Hydrated from the UTC column, so the scheduled report holds it in the site's time zone.
    $lastSentAt = DateTimeHelper::toDateTime('2026-11-16 20:00:00');
    $scheduled = new ScheduledReport(['reportId' => $report->id, 'lastSentAt' => $lastSentAt]);

    $query = Formie::$plugin->getReportScheduledDelivery()->buildSubmissionQuery($report, $scheduled);

    expect(array_map('intval', $query->ids()))->toBe([$ids['after last send']])
        ->and(Formie::$plugin->getReportQuery()->getSummaryCounts($report, null, $lastSentAt)['total'])->toBe(1);
});

it('reads scheduled delivery start and end dates in site time', function(string $timeZone, string $startAt, string $endAt, string $siteNow, bool $expected): void {
    Craft::$app->setTimeZone($timeZone);
    $schedule = new ScheduledReport(['name' => 'Site time zone', 'reportId' => 1]);
    $schedule->setDeliveryModel(ScheduledReportDelivery::fromArray([
        'frequency' => 'daily', 'hour' => 8, 'startAt' => $startAt, 'endAt' => $endAt,
        'recipients' => ['fixture@example.test'],
    ]));

    expect(Formie::$plugin->getScheduledReports()->isDue($schedule, new DateTime($siteNow, new DateTimeZone($timeZone))))->toBe($expected);
})->with([
    'east start day delivery' => ['Australia/Melbourne', '2026-09-17', '2026-09-30', '2026-09-17 08:00:00', true],
    'east before start day' => ['Australia/Melbourne', '2026-09-17', '2026-09-30', '2026-09-16 23:59:59', false],
    'west end day delivery' => ['America/New_York', '2026-09-01', '2026-09-17', '2026-09-17 20:00:00', true],
    'west after end day' => ['America/New_York', '2026-09-01', '2026-09-17', '2026-09-18 08:00:00', false],
    'east explicit cutoff' => ['Australia/Melbourne', '2026-09-01', '2026-09-17 12:00:00', '2026-09-17 12:00:01', false],
    'east before explicit cutoff' => ['Australia/Melbourne', '2026-09-01', '2026-09-17 12:00:00', '2026-09-17 11:59:59', true],
]);
