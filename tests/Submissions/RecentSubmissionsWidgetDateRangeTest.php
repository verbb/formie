<?php

declare(strict_types=1);

use craft\elements\User;
use craft\events\TemplateEvent;
use craft\helpers\Db;
use craft\web\View;
use verbb\formie\widgets\RecentSubmissions;
use yii\base\Event;

// The widget's date range picker posts each chosen day as midnight in the browser, and the dashboard
// stores it as is. Custom ranges cover whole site days like the presets, so the "To" day is included
// and line charts get one zero-filled point per chosen day or month, in date order. The picker also
// saves one-sided ranges ("From …" or "To …"), which line charts must render rather than fail on.

beforeEach(function(): void {
    $this->originalTimeZone = Craft::$app->getTimeZone();
    $this->originalIdentity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
});

afterEach(function(): void {
    Craft::$app->setTimeZone($this->originalTimeZone);
    Craft::$app->getUser()->setIdentity($this->originalIdentity);
});

function recentSubmissionsWidgetForm(array $utcDates): int
{
    $form = formie()->form()->singleLineTextField('message')->create();

    foreach ($utcDates as $utcDate) {
        $submission = formie()->submission($form)->with(['message' => $utcDate])->save();
        Db::update('{{%elements}}', ['dateCreated' => $utcDate], ['id' => $submission->id]);
    }

    return (int)$form->id;
}

/**
 * Saves the widget through the dashboard and reloads it, as the CP does, then returns its chart variables.
 */
function recentSubmissionsWidgetChart(array $settings): array
{
    $dashboard = Craft::$app->getDashboard();
    $widget = $dashboard->createWidget(['type' => RecentSubmissions::class, 'settings' => $settings]);
    expect($dashboard->saveWidget($widget))->toBeTrue();

    $widget = $dashboard->getWidgetById((int)$widget->id);
    $captured = [];
    $handler = static function(TemplateEvent $event) use (&$captured): void {
        if ($event->template === 'formie/widgets/submissions/body') {
            $captured = $event->variables;
            $event->isValid = false;
        }
    };

    Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, $handler);

    try {
        $widget->getBodyHtml();
    } finally {
        Event::off(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, $handler);
    }

    // Database drivers can return counts as strings
    $data = array_map('intval', $captured['datasets'][0]['data'] ?? []);
    $total = $captured['totalSubmissions'][0] ?? null;

    return [
        'total' => $total === null ? null : (int)$total,
        'line' => $data ? array_combine($captured['labels'], $data) : [],
    ];
}

it('includes the whole To day of a custom range', function(string $displayType, string $start, string $end, int|array $expected): void {
    Craft::$app->setTimeZone('UTC');
    $formId = recentSubmissionsWidgetForm([
        '2026-11-14 23:59:59',
        '2026-11-15 00:00:00',
        '2026-11-16 10:00:00',
        '2026-11-16 23:59:59',
        '2026-11-17 00:00:00',
    ]);

    // The picker posts Unix timestamps, so mirror the request the CP sends
    $chart = recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => $displayType,
        'dateRange' => 'custom',
        'startDate' => (string)strtotime("$start 00:00:00 UTC"),
        'endDate' => (string)strtotime("$end 00:00:00 UTC"),
    ]);

    expect($chart[$displayType === 'pie' ? 'total' : 'line'])->toBe($expected);
})->with([
    'single day pie' => ['pie', '2026-11-16', '2026-11-16', 2],
    'two day pie' => ['pie', '2026-11-15', '2026-11-16', 3],
    'single day line' => ['line', '2026-11-16', '2026-11-16', ['2026-11-16' => 2]],
    'two day line' => ['line', '2026-11-15', '2026-11-16', ['2026-11-15' => 1, '2026-11-16' => 2]],
]);

it('zero-fills a custom line chart across exactly the chosen days', function(): void {
    Craft::$app->setTimeZone('UTC');
    $formId = recentSubmissionsWidgetForm([
        '2026-10-31 12:00:00',
        '2026-11-03 10:00:00',
        '2026-11-05 10:00:00',
        '2026-11-05 11:00:00',
        '2026-11-09 10:00:00',
        '2026-11-10 10:00:00',
    ]);

    // Stored settings from a widget saved before this fix keep working
    $chart = recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => 'line',
        'dateRange' => 'custom',
        'startDate' => '2026-11-01T00:00:00+00:00',
        'endDate' => '2026-11-09T00:00:00+00:00',
    ]);

    expect($chart['line'])->toBe([
        '2026-11-01' => 0,
        '2026-11-02' => 0,
        '2026-11-03' => 1,
        '2026-11-04' => 0,
        '2026-11-05' => 2,
        '2026-11-06' => 0,
        '2026-11-07' => 0,
        '2026-11-08' => 0,
        '2026-11-09' => 1,
    ]);
});

it('zero-fills every month of a long custom range that starts late in a month', function(): void {
    Craft::$app->setTimeZone('UTC');
    $formId = recentSubmissionsWidgetForm([
        '2026-01-31 10:00:00',
        '2026-03-15 10:00:00',
        '2026-05-10 23:00:00',
        '2026-05-11 10:00:00',
    ]);

    // Over 90 days, so the chart groups by month
    $chart = recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => 'line',
        'dateRange' => 'custom',
        'startDate' => '2026-01-31T00:00:00+00:00',
        'endDate' => '2026-05-10T00:00:00+00:00',
    ]);

    expect($chart['line'])->toBe([
        '2026-1' => 1,
        '2026-2' => 0,
        '2026-3' => 1,
        '2026-4' => 0,
        '2026-5' => 1,
    ]);
});

it('bounds a custom range by site days outside UTC', function(string $timeZone, string $offset, array $utcDates): void {
    Craft::$app->setTimeZone($timeZone);
    $formId = recentSubmissionsWidgetForm($utcDates);

    // A browser in the site's time zone posts its own midnight for 16 November
    $settings = [
        'formIds' => [$formId],
        'dateRange' => 'custom',
        'startDate' => "2026-11-16T00:00:00$offset",
        'endDate' => "2026-11-16T00:00:00$offset",
    ];

    expect(recentSubmissionsWidgetChart($settings + ['displayType' => 'pie'])['total'])->toBe(2)
        ->and(recentSubmissionsWidgetChart($settings + ['displayType' => 'line'])['line'])->toBe(['2026-11-16' => 2]);
})->with([
    // 16 November in Melbourne runs from 13:00 UTC on the 15th to 13:00 UTC on the 16th.
    'east of UTC' => ['Australia/Melbourne', '+11:00', [
        '2026-11-15 12:30:00',
        '2026-11-15 13:30:00',
        '2026-11-16 12:45:00',
        '2026-11-16 13:15:00',
    ]],
    // 16 November in New York runs from 05:00 UTC on the 16th to 05:00 UTC on the 17th.
    'west of UTC' => ['America/New_York', '-05:00', [
        '2026-11-16 04:30:00',
        '2026-11-16 05:30:00',
        '2026-11-17 04:45:00',
        '2026-11-17 05:15:00',
    ]],
]);

it('only bounds the side of an open-ended custom range that is set', function(): void {
    Craft::$app->setTimeZone('UTC');
    $formId = recentSubmissionsWidgetForm([
        '2026-11-14 10:00:00',
        '2026-11-16 10:00:00',
        '2026-11-18 10:00:00',
    ]);

    $chart = static fn(array $range): ?int => recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => 'pie',
        'dateRange' => 'custom',
    ] + $range)['total'];

    expect($chart(['startDate' => '2026-11-16T00:00:00+00:00']))->toBe(2)
        ->and($chart(['endDate' => '2026-11-16T00:00:00+00:00']))->toBe(2);
});

it('draws a line chart for a custom range with only a From day, up to today', function(): void {
    Craft::$app->setTimeZone('UTC');
    $day = static fn(int $offset): string => (new DateTime("today $offset days", new DateTimeZone('UTC')))->format('Y-m-d');
    $formId = recentSubmissionsWidgetForm([
        $day(-5) . ' 10:00:00',
        $day(-3) . ' 10:00:00',
        $day(-1) . ' 10:00:00',
    ]);

    // As saved by the CP: the missing side is stored as null
    $chart = recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => 'line',
        'dateRange' => 'custom',
        'startDate' => $day(-3) . 'T00:00:00+00:00',
        'endDate' => null,
    ]);

    expect($chart['line'])->toBe([
        $day(-3) => 1,
        $day(-2) => 0,
        $day(-1) => 1,
        $day(0) => 0,
    ]);
});

it('draws a line chart for a custom range with only a To day, from the earliest submission', function(): void {
    Craft::$app->setTimeZone('UTC');
    $formId = recentSubmissionsWidgetForm([
        '2026-11-05 10:00:00',
        '2026-11-07 10:00:00',
        '2026-11-10 10:00:00',
    ]);

    $chart = recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => 'line',
        'dateRange' => 'custom',
        'startDate' => null,
        'endDate' => '2026-11-09T00:00:00+00:00',
    ]);

    expect($chart['line'])->toBe([
        '2026-11-05' => 1,
        '2026-11-06' => 0,
        '2026-11-07' => 1,
        '2026-11-08' => 0,
        '2026-11-09' => 0,
    ]);

    // With nothing on or before the To day, there is nothing to draw
    $emptyFormId = recentSubmissionsWidgetForm(['2026-11-10 10:00:00']);

    expect(recentSubmissionsWidgetChart([
        'formIds' => [$emptyFormId],
        'displayType' => 'line',
        'dateRange' => 'custom',
        'endDate' => '2026-11-09T00:00:00+00:00',
    ])['line'])->toBe([]);
});

it('orders month points by date when the range passes October', function(string $start, string $end, array $expected): void {
    Craft::$app->setTimeZone('UTC');
    $formId = recentSubmissionsWidgetForm([
        '2025-12-15 12:00:00',
        '2026-01-15 12:00:00',
        '2026-07-15 12:00:00',
        '2026-10-15 12:00:00',
        '2026-11-15 12:00:00',
    ]);

    $chart = recentSubmissionsWidgetChart([
        'formIds' => [$formId],
        'displayType' => 'line',
        'dateRange' => 'custom',
        'startDate' => "{$start}T00:00:00+00:00",
        'endDate' => "{$end}T00:00:00+00:00",
    ]);

    expect($chart['line'])->toBe($expected);
})->with([
    'within a year' => ['2026-07-01', '2026-11-30', ['2026-7' => 1, '2026-8' => 0, '2026-9' => 0, '2026-10' => 1, '2026-11' => 1]],
    'across a year' => ['2025-10-01', '2026-02-28', ['2025-10' => 0, '2025-11' => 0, '2025-12' => 1, '2026-1' => 1, '2026-2' => 0]],
]);
