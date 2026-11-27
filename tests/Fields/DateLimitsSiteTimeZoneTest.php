<?php

declare(strict_types=1);

use craft\helpers\DateTimeHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;

// Min Date and Max Date are calendar days. Today's Date/Time is the site's day, and submitted date parts and the limits
// are compared as UTC wall-clock values. Each case runs where the site's day differs from UTC's, or where converting
// the limit to the site's time zone would move it to another day.

beforeEach(function(): void {
    $this->originalTimeZone = Craft::$app->getTimeZone();
    $this->originalFormatterTimeZone = Craft::$app->getFormatter()->timeZone;
});

afterEach(function(): void {
    DateTimeHelper::resume();
    Craft::$app->setTimeZone($this->originalTimeZone);
    Craft::$app->getFormatter()->timeZone = $this->originalFormatterTimeZone;
});

function dateLimitsUseSiteTimeZone(string $timeZone, ?string $nowUtc = null): void
{
    // The formatter reads the app's time zone when it's created, so set it as well, as a request in that zone would.
    Craft::$app->setTimeZone($timeZone);
    Craft::$app->getFormatter()->timeZone = $timeZone;

    if ($nowUtc) {
        DateTimeHelper::pause(new DateTime($nowUtc, new DateTimeZone('UTC')));
    }
}

function dateLimitsField(array $config): Date
{
    $rows = (new Date(['displayType' => 'inputs']))->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$subField) {
            if (in_array($subField['handle'], ['hour', 'minute', 'second', 'ampm'], true)) {
                $subField['enabled'] = false;
            }
        }
    }

    unset($row, $subField);

    $field = new Date(array_merge(['handle' => 'eventDate', 'displayType' => 'inputs'], $config));
    $field->setRows($rows);

    return $field;
}

function dateLimitsErrors(Date $field, Submission $submission, string $date): array
{
    [$year, $month, $day] = explode('-', $date);

    $submission->method('getFieldValue')->willReturnMap([
        [$field->getFieldByHandle('year')->valueKey(), $year],
        [$field->getFieldByHandle('month')->valueKey(), (string)(int)$month],
        [$field->getFieldByHandle('day')->valueKey(), (string)(int)$day],
    ]);

    $field->validateDateParts($submission);

    return $submission->getErrors($field->valueKey());
}

function dateLimitsBrowserRule(Date $field): array
{
    foreach ($field->browserValidationRules() as $rule) {
        if ($rule['type'] === 'dateParts') {
            return array_intersect_key($rule, array_flip(['minDate', 'maxDate']));
        }
    }

    return [];
}

it('resolves Today limits and their offsets to the site day', function(string $timeZone, string $nowUtc, array $config, ?string $expectedMin, ?string $expectedMax): void {
    dateLimitsUseSiteTimeZone($timeZone, $nowUtc);

    $field = dateLimitsField($config);

    expect($field->getMinDate()?->format('Y-m-d\TH:i:sP'))->toBe($expectedMin)
        ->and($field->getMaxDate()?->format('Y-m-d\TH:i:sP'))->toBe($expectedMax)
        ->and(dateLimitsBrowserRule($field))->toBe(array_filter([
            'minDate' => $expectedMin ? substr($expectedMin, 0, 19) : null,
            'maxDate' => $expectedMax ? substr($expectedMax, 0, 19) : null,
        ]));
})->with([
    'west of UTC in the evening' => [
        'America/New_York', '2026-11-18 01:30:00',
        ['minDateOption' => 'today', 'maxDateOption' => 'today'],
        '2026-11-17T00:00:00+00:00', '2026-11-17T23:59:59+00:00',
    ],
    'east of UTC in the morning' => [
        'Australia/Melbourne', '2026-11-17 14:30:00',
        ['minDateOption' => 'today', 'maxDateOption' => 'today'],
        '2026-11-18T00:00:00+00:00', '2026-11-18T23:59:59+00:00',
    ],
    'west of UTC with day offsets' => [
        'America/New_York', '2026-11-18 01:30:00',
        ['minDateOption' => 'today', 'minDateOffset' => 'subtract', 'minDateOffsetNumber' => 2, 'minDateOffsetType' => 'days', 'maxDateOption' => 'today', 'maxDateOffset' => 'add', 'maxDateOffsetNumber' => 3, 'maxDateOffsetType' => 'days'],
        '2026-11-15T00:00:00+00:00', '2026-11-20T23:59:59+00:00',
    ],
    'east of UTC with week and month offsets' => [
        'Australia/Melbourne', '2026-11-30 14:30:00',
        ['minDateOption' => 'today', 'minDateOffset' => 'add', 'minDateOffsetNumber' => 1, 'minDateOffsetType' => 'weeks', 'maxDateOption' => 'today', 'maxDateOffset' => 'add', 'maxDateOffsetNumber' => 1, 'maxDateOffsetType' => 'months'],
        '2026-12-08T00:00:00+00:00', '2027-01-01T23:59:59+00:00',
    ],
    'west of UTC on New Year’s Eve with a year offset' => [
        'America/Los_Angeles', '2027-01-01 06:00:00',
        ['minDateOption' => 'today', 'minDateOffset' => 'subtract', 'minDateOffsetNumber' => 1, 'minDateOffsetType' => 'years'],
        '2025-12-31T00:00:00+00:00', null,
    ],
]);

it('validates Today limits against the site day', function(string $timeZone, string $nowUtc, string $option, string $date, bool $valid): void {
    dateLimitsUseSiteTimeZone($timeZone, $nowUtc);

    $field = dateLimitsField([$option => 'today']);
    $submission = $this->getMockBuilder(Submission::class)
        ->onlyMethods(['getFieldValue'])
        ->getMock();

    expect(dateLimitsErrors($field, $submission, $date) === [])->toBe($valid);
})->with([
    'west min accepts the site day' => ['America/New_York', '2026-11-18 01:30:00', 'minDateOption', '2026-11-17', true],
    'west min rejects the site’s yesterday' => ['America/New_York', '2026-11-18 01:30:00', 'minDateOption', '2026-11-16', false],
    'west max accepts the site day' => ['America/New_York', '2026-11-18 01:30:00', 'maxDateOption', '2026-11-17', true],
    'west max rejects the site’s tomorrow' => ['America/New_York', '2026-11-18 01:30:00', 'maxDateOption', '2026-11-18', false],
    'east min accepts the site day' => ['Australia/Melbourne', '2026-11-17 14:30:00', 'minDateOption', '2026-11-18', true],
    'east min rejects the site’s yesterday' => ['Australia/Melbourne', '2026-11-17 14:30:00', 'minDateOption', '2026-11-17', false],
    'east max accepts the site day' => ['Australia/Melbourne', '2026-11-17 14:30:00', 'maxDateOption', '2026-11-18', true],
    'east max rejects the site’s tomorrow' => ['Australia/Melbourne', '2026-11-17 14:30:00', 'maxDateOption', '2026-11-19', false],
]);

it('names the limit’s own day in the error message', function(string $timeZone, ?string $nowUtc, array $config, string $date, string $expected, string $limitDay): void {
    dateLimitsUseSiteTimeZone($timeZone, $nowUtc);

    $field = dateLimitsField($config);
    $submission = $this->getMockBuilder(Submission::class)
        ->onlyMethods(['getFieldValue'])
        ->getMock();

    // A date-only string is formatted without time zone conversion, so it names the expected day in any zone.
    expect(dateLimitsErrors($field, $submission, $date))->toBe([
        Craft::t('formie', $expected, ['date' => Craft::$app->getFormatter()->asDate($limitDay)]),
    ]);
})->with([
    'west of UTC, specific min date' => [
        'America/New_York', null, ['minDateOption' => 'date', 'minDate' => '2026-12-01'],
        '2026-11-30', 'The date must be on or after {date}.', '2026-12-01',
    ],
    'east of UTC, specific max date' => [
        'Australia/Melbourne', null, ['maxDateOption' => 'date', 'maxDate' => '2026-12-31'],
        '2027-01-01', 'The date must be on or before {date}.', '2026-12-31',
    ],
    'west of UTC, Today min date' => [
        'America/New_York', '2026-11-18 01:30:00', ['minDateOption' => 'today'],
        '2026-11-16', 'The date must be on or after {date}.', '2026-11-17',
    ],
    'east of UTC, Today max date' => [
        'Australia/Melbourne', '2026-11-17 14:30:00', ['maxDateOption' => 'today'],
        '2026-11-19', 'The date must be on or before {date}.', '2026-11-18',
    ],
]);

it('gives the browser rule the same Min Date and Max Date messages as the server', function(string $timeZone, string $date): void {
    dateLimitsUseSiteTimeZone($timeZone);

    $field = dateLimitsField([
        'minDateOption' => 'date', 'minDate' => '2026-12-01',
        'maxDateOption' => 'date', 'maxDate' => '2027-06-30',
    ]);
    $messages = [];

    foreach ($field->browserValidationRules() as $rule) {
        if ($rule['type'] === 'dateParts') {
            $messages = $rule['messages'];
        }
    }

    $serverError = function(string $date) use ($field): string {
        $submission = $this->getMockBuilder(Submission::class)->onlyMethods(['getFieldValue'])->getMock();

        return dateLimitsErrors($field, $submission, $date)[0];
    };

    expect($messages['dateMin'] ?? null)->toBe($serverError('2026-11-30'))
        ->and($messages['dateMin'])->toContain('2026')
        ->and($messages['dateMax'] ?? null)->toBe($serverError($date));
})->with([
    'west of UTC' => ['America/New_York', '2027-07-01'],
    'east of UTC' => ['Australia/Melbourne', '2027-07-01'],
]);
