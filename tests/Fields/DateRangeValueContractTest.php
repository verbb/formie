<?php

declare(strict_types=1);

use verbb\formie\fields\Date;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\DateRangeFieldValue;

it('normalizes and serializes calendar date range values', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
        'dateFormat' => 'Y-m-d',
        'timeFormat' => 'H:i',
    ]);

    $value = $field->normalizeValue([
        'startDate' => '2026-06-01',
        'endDate' => '2026-06-10',
    ], null);

    expect($value)->toBeInstanceOf(DateRangeFieldValue::class)
        ->and($value->getStartParts())->toMatchArray([
            'year' => '2026',
            'month' => '6',
            'day' => '1',
        ])
        ->and($value->getEndParts())->toMatchArray([
            'year' => '2026',
            'month' => '6',
            'day' => '10',
        ]);

    $serialized = $field->serializeValue($value, null);

    expect($serialized)->toBeArray()
        ->and($serialized['start'])->toMatchArray([
            'year' => '2026',
            'month' => '6',
            'day' => '1',
        ])
        ->and($serialized['end'])->toMatchArray([
            'year' => '2026',
            'month' => '6',
            'day' => '10',
        ]);
});

it('resolves range sub-field values by prefixed handles', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
    ]);

    $value = $field->normalizeValue([
        'startDate' => '2026-06-01',
        'endDate' => '2026-06-10',
    ], null);

    expect($field->getSubFieldPartValue($value, 'startYear'))->toBe('2026')
        ->and($field->getSubFieldPartValue($value, 'endDay'))->toBe('10');

    $rangeValue = DateRangeFieldValue::fromMixed($value);

    expect($rangeValue->getPathValue('startYear'))->toBe('2026')
        ->and($rangeValue->getPathValue('endMonth'))->toBe('6')
        ->and($rangeValue->getPathValue('startDate'))->toBe('2026-06-01')
        ->and($rangeValue->getPathValue('endDate'))->toBe('2026-06-10')
        ->and($field->getSubFieldPartValue($value, 'startDate'))->toBe('2026-06-01')
        ->and($field->getSubFieldPartValue($value, 'endDate'))->toBe('2026-06-10');
});

it('resolves stored range date parts for cp submission sub-fields', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
    ]);

    $value = DateRangeFieldValue::fromMixed([
        'start' => [
            'day' => '16',
            'hour' => '12',
            'year' => '2026',
            'month' => '6',
            'minute' => '0',
            'second' => '0',
        ],
        'end' => [
            'day' => '27',
            'hour' => '12',
            'year' => '2026',
            'month' => '6',
            'minute' => '0',
            'second' => '0',
        ],
    ]);

    expect($field->getSubFieldPartValue($value, 'startDate'))->toBe('2026-06-16')
        ->and($field->getSubFieldPartValue($value, 'endDate'))->toBe('2026-06-27')
        ->and($field->getSubFieldPartValue($value, 'startTime'))->toBe('12:00')
        ->and($field->getSubFieldPartValue($value, 'endTime'))->toBe('12:00')
        ->and($field->resolveNormalizedValuePath($value, 'start'))->toBe('2026-06-16 12:00')
        ->and($field->resolveNormalizedValuePath($value, 'end'))->toBe('2026-06-27 12:00');
});

it('exposes granular range reference selectors for variable pickers', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
    ]);

    $selectorHandles = array_map(
        static fn($selector) => $selector->handle,
        $field->references()->selectors,
    );

    expect($field->references()->primaryTokenSuffix)->toBe('__toString')
        ->and($selectorHandles)->toContain(
            'start',
            'end',
            'startDate',
            'startTime',
            'endDate',
            'endTime',
        );

    $labelsByHandle = [];

    foreach ($field->references()->selectors as $selector) {
        $labelsByHandle[$selector->handle] = $selector->label;
    }

    expect($labelsByHandle['__toString'])->toBe('Formatted Date')
        ->and($labelsByHandle['startDate'])->toBe('Start Date')
        ->and($labelsByHandle['endDate'])->toBe('End Date');
});

it('includes range and single date reference selectors in field type config', function (): void {
    $field = new Date();
    $selectors = $field->getFieldTypeConfig()['referenceConfig']['selectors'] ?? [];
    $handles = array_column($selectors, 'handle');

    expect($handles)->toContain('startDate', 'endDate', 'date', 'time');

    $conditionsByHandle = [];

    foreach ($selectors as $selector) {
        $conditionsByHandle[$selector['handle']] = $selector['condition'] ?? null;
    }

    expect($conditionsByHandle['startDate'])->toBe('collectMode == "range" && displayType == "datePicker"')
        ->and($conditionsByHandle['date'])->toBe('displayType == "calendar" || (displayType == "datePicker" && collectMode != "range")');
});

it('does not expose range-only reference selectors on single date fields', function (): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => 'datePicker',
    ]);

    $conditionsByHandle = [];

    foreach ($field->references()->selectors as $selector) {
        $conditionsByHandle[$selector->handle] = $selector->condition;
    }

    expect($conditionsByHandle['date'])->toContain('collectMode != "range"')
        ->and($conditionsByHandle['time'])->toContain('collectMode != "range"')
        ->and($conditionsByHandle['startDate'])->toContain('collectMode == "range"')
        ->and($conditionsByHandle['endDate'])->toContain('collectMode == "range"');
});

it('generates fake email preview values for date ranges', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
    ]);

    $faker = Faker\Factory::create();
    $previewValue = $field->getValueForEmailPreview($faker);
    $normalized = $field->normalizeValue($previewValue, null);

    expect($normalized)->toBeInstanceOf(DateRangeFieldValue::class)
        ->and($normalized->isEmpty())->toBeFalse()
        ->and($field->getValueAsString($normalized, null))->not->toBe('')
        ->and($field->isValueEmpty($normalized, null))->toBeFalse();
});

it('formats stored date ranges for summaries and detects invalid ordering', function (): void {
    $field = new Date([
        'handle' => 'stayDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
    ]);

    $validValue = new DateRangeFieldValue([
        'start' => ['year' => '2026', 'month' => '6', 'day' => '1'],
        'end' => ['year' => '2026', 'month' => '6', 'day' => '10'],
    ]);

    expect($field->getValueAsString($validValue, null))->toBe('2026-06-01 – 2026-06-10');

    $invalidValue = new DateRangeFieldValue([
        'start' => ['year' => '2026', 'month' => '6', 'day' => '10'],
        'end' => ['year' => '2026', 'month' => '6', 'day' => '1'],
    ]);

    $start = DateFieldValue::partsToDateTime($invalidValue->getStartParts());
    $end = DateFieldValue::partsToDateTime($invalidValue->getEndParts());

    expect($start)->toBeInstanceOf(\DateTime::class)
        ->and($end)->toBeInstanceOf(\DateTime::class)
        ->and($end < $start)->toBeTrue();
});

it('defaults collect mode to single for existing date fields', function (): void {
    $field = new Date([
        'handle' => 'eventDate',
    ]);

    expect($field->collectMode)->toBe(Date::COLLECT_SINGLE)
        ->and($field->getCollectsRange())->toBeFalse();
});

it('does not collect range outside calendar advanced display type', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'calendar',
    ]);

    expect($field->getCollectsRange())->toBeFalse();
});

it('normalizes flatpickr range datetime input names', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
    ]);

    $value = $field->normalizeValue([
        'startDatetime' => '2026-06-01 09:30:00',
        'endDatetime' => '2026-06-10 17:00:00',
    ], null);

    expect($value)->toBeInstanceOf(DateRangeFieldValue::class)
        ->and($value->getStartParts()['day'] ?? null)->toBe('1')
        ->and($value->getEndParts()['day'] ?? null)->toBe('10');
});

it('includes single and range sub-field handles in calendar nested layout builder config', function (): void {
    $field = new Date();
    $config = $field->getFieldTypeConfigData();
    $calendarHandles = $config['nestedLayoutBuilder']['layouts']['layouts.calendar']['allowedHandles'] ?? [];
    $calendarRangeHandles = $config['nestedLayoutBuilder']['layouts']['layouts.calendarRange']['allowedHandles'] ?? [];
    $dropdownHandles = $config['nestedLayoutBuilder']['layouts']['layouts.dropdowns']['allowedHandles'] ?? [];

    expect($calendarHandles)->toContain('date', 'time')
        ->and($calendarHandles)->not->toContain('startDate', 'endDate')
        ->and($calendarRangeHandles)->toContain('startDate', 'startTime', 'endDate', 'endTime')
        ->and($dropdownHandles)->toContain('year', 'month', 'day')
        ->and($dropdownHandles)->not->toContain('startYear', 'endYear');
});

it('does not auto-generate format placeholders for date picker range fields', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
        'dateFormat' => 'Y-m-d',
        'timeFormat' => 'H:i',
    ]);

    expect($field->getEffectivePlaceholder())->toBeNull();
});

it('uses a custom placeholder for date picker range fields', function (): void {
    $field = new Date([
        'handle' => 'bookingDates',
        'collectMode' => Date::COLLECT_RANGE,
        'displayType' => 'datePicker',
        'placeholder' => 'Pick your dates',
    ]);

    expect($field->getEffectivePlaceholder())->toBe('Pick your dates');
});
