<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\content\SubmissionContentAccessor;
use verbb\formie\elements\Submission;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\fields\Date;
use verbb\formie\helpers\ArrayHelper;

it('preserves partial date parts for inputs without coercing to a fake datetime', function (): void {
    $field = new Date([
        'handle' => 'dob',
        'displayType' => 'inputs',
    ]);

    $value = $field->normalizeValue([
        'year' => '2026',
        'month' => '02',
        'day' => '',
        'hour' => '',
        'minute' => '',
    ], null);

    expect($value)->toBeInstanceOf(DateFieldValue::class)
        ->and($value->getPart('year'))->toBe('2026')
        ->and($value->getPart('month'))->toBe('2');

    $serialized = $field->serializeValue($value, null);

    expect($serialized)->toBeArray()
        ->and($serialized)->toMatchArray([
            'year' => '2026',
            'month' => '2',
        ]);
});

it('serializes complete date parts as canonical part maps', function (): void {
    $field = new Date([
        'handle' => 'dob',
        'displayType' => 'inputs',
    ]);

    $value = $field->normalizeValue([
        'year' => '2026',
        'month' => '02',
        'day' => '03',
        'hour' => '04',
        'minute' => '05',
        'second' => '06',
    ], null);

    $serialized = $field->serializeValue($value, null);

    expect($serialized)->toBeArray()
        ->and($serialized)->toMatchArray([
            'year' => '2026',
            'month' => '2',
            'day' => '3',
            'hour' => '4',
            'minute' => '5',
            'second' => '6',
        ]);
});

it('resolves sub-field part values as scalar values', function (): void {
    $field = new Date([
        'handle' => 'dob',
        'displayType' => 'inputs',
    ]);

    $dateData = $field->normalizeValue([
        'year' => '2025',
        'month' => '1',
        'day' => '24',
        'hour' => '13',
        'minute' => '50',
    ], null);

    expect($field->getSubFieldPartValue($dateData, 'year'))->toBe('2025')
        ->and($field->getSubFieldPartValue($dateData, 'month'))->toBe('1')
        ->and($field->getSubFieldPartValue(new SingleOptionFieldValue('May', '5', true), 'month'))->toBe('5');
});

it('resolves single date and time reference selectors from stored parts', function (): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => 'datePicker',
        'dateFormat' => 'Y-m-d',
        'timeFormat' => 'H:i:s',
    ]);

    $value = $field->normalizeValue([
        'year' => '2020',
        'month' => '8',
        'day' => '22',
        'hour' => '11',
        'minute' => '57',
        'second' => '51',
        'ampm' => 'AM',
    ], null);

    expect($field->resolveNormalizedValuePath($value, 'date'))->toBe('2020-08-22')
        ->and($field->resolveNormalizedValuePath($value, 'time'))->toBe('11:57:51')
        ->and($field->getSubFieldPartValue($value, 'date'))->toBe('2020-08-22')
        ->and($field->getSubFieldPartValue($value, 'time'))->toBe('11:57:51')
        ->and($field->formatPartsForDisplay($value->getParts()))->toBe('2020-08-22 11:57:51');
});

it('stringifies normalized date values consistently for field output', function (): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => 'datePicker',
        'dateFormat' => 'Y-m-d',
        'timeFormat' => 'H:i',
    ]);

    $value = $field->normalizeValue([
        'year' => '1984',
        'month' => '7',
        'day' => '14',
        'hour' => '18',
        'minute' => '46',
        'second' => '8',
        'ampm' => 'PM',
    ], null);

    expect((string)$value)->toBe('1984-07-14 18:46')
        ->and($field->getValueAsString($value, null))->toBe('1984-07-14 18:46')
        ->and($value->getPathValue('date'))->toBe('1984-07-14')
        ->and($value->getPathValue('time'))->toBe('18:46');
});

it('resolves single date and time reference selectors from faker preview values', function (): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => 'datePicker',
    ]);

    $faker = Faker\Factory::create();
    $previewValue = $field->getValueForEmailPreview($faker);
    $normalized = $field->normalizeValue($previewValue, null);

    expect($normalized)->toBeInstanceOf(DateFieldValue::class)
        ->and($normalized->getPathValue('date'))->not->toBe('')
        ->and($normalized->getPathValue('time'))->not->toBe('');
});

it('validates date number sub-fields using n j and G format params', function (): void {
    $form = formie()
        ->form(['title' => 'Date Input Validation Tokens'])
        ->dateField('dateInputs', [
            'displayType' => 'inputs',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $submission = formie()->submission($form)
        ->with([
            'dateInputs' => [
                'year' => '2024',
                'month' => '2',
                'day' => '25',
                'hour' => '13',
                'minute' => '50',
            ],
        ])
        ->allowValidationFailure()
        ->save();

    expect($submission)->not->toHaveFieldError('dateInputs.year')
        ->and($submission)->not->toHaveFieldError('dateInputs.month')
        ->and($submission)->not->toHaveFieldError('dateInputs.day')
        ->and($submission)->not->toHaveFieldError('dateInputs.hour')
        ->and($submission)->not->toHaveFieldError('dateInputs.minute');
});

it('validates date dropdown sub-fields from their own option values', function (): void {
    $form = formie()
        ->form(['title' => 'Date Dropdown Validation Tokens'])
        ->dateField('dateDropdowns', [
            'displayType' => 'dropdowns',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $submission = formie()->submission($form)
        ->with([
            'dateDropdowns' => [
                'year' => '1935',
                'month' => '8',
                'day' => '10',
                'hour' => '6',
                'minute' => '7',
            ],
        ])
        ->allowValidationFailure()
        ->save();

    expect($submission)->not->toHaveFieldError('dateDropdowns.year')
        ->and($submission)->not->toHaveFieldError('dateDropdowns.month')
        ->and($submission)->not->toHaveFieldError('dateDropdowns.day')
        ->and($submission)->not->toHaveFieldError('dateDropdowns.hour')
        ->and($submission)->not->toHaveFieldError('dateDropdowns.minute');
});

it('accepts year-only dropdown values when other date parts are disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Date Year Dropdown Only'])
        ->dateField('birthYear', [
            'displayType' => 'dropdowns',
            'required' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('birthYear');
    expect($field)->toBeInstanceOf(Date::class);

    $field->setRows((new Date(['displayType' => 'dropdowns']))->getSubFields());

    foreach ($field->getFields() as $subField) {
        if ($subField->handle === 'year') {
            $subField->required = true;
            continue;
        }

        $subField->enabled = false;
        $subField->required = false;
    }

    Formie::$plugin->getForms()->saveForm($form);

    $submission = formie()->submission($form)
        ->with([
            'birthYear' => [
                'year' => '2011',
            ],
        ])
        ->allowValidationFailure()
        ->save();

    $stored = $submission->getFieldValue('birthYear');

    expect($submission)->not->toHaveFieldError('birthYear')
        ->and($submission)->not->toHaveFieldError('birthYear.year')
        ->and($stored)->toBeInstanceOf(DateFieldValue::class)
        ->and($stored->getPart('year'))->toBe('2011');
});

it('rejects impossible calendar dates for text input sub-fields', function (): void {
    $field = new Date([
        'handle' => 'birthday',
        'displayType' => 'inputs',
    ]);
    $field->setRows((new Date(['displayType' => 'inputs']))->getSubFields());

    $yearField = $field->getFieldByHandle('year');
    $monthField = $field->getFieldByHandle('month');
    $dayField = $field->getFieldByHandle('day');

    $submission = $this->getMockBuilder(Submission::class)
        ->onlyMethods(['getFieldValue'])
        ->getMock();
    $submission->method('getFieldValue')->willReturnMap([
        [$yearField->valueKey(), '2024'],
        [$monthField->valueKey(), '2'],
        [$dayField->valueKey(), '31'],
    ]);

    $field->validateDateParts($submission);

    expect($submission->getErrors($dayField->valueKey()))->not->toBeEmpty();
});

it('rejects text input dates before the configured minimum date', function (): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => 'inputs',
        'minDateOption' => 'date',
        'minDate' => '2020-01-01',
    ]);
    $field->setRows((new Date(['displayType' => 'inputs']))->getSubFields());

    $yearField = $field->getFieldByHandle('year');
    $monthField = $field->getFieldByHandle('month');
    $dayField = $field->getFieldByHandle('day');

    $submission = $this->getMockBuilder(Submission::class)
        ->onlyMethods(['getFieldValue'])
        ->getMock();
    $submission->method('getFieldValue')->willReturnMap([
        [$yearField->valueKey(), '2019'],
        [$monthField->valueKey(), '12'],
        [$dayField->valueKey(), '31'],
    ]);

    $field->validateDateParts($submission);

    expect($submission->getErrors($field->valueKey()))->not->toBeEmpty();
});

it('returns null when converting impossible calendar parts to datetime', function (): void {
    expect(DateFieldValue::partsToDateTime([
        'year' => '2024',
        'month' => '2',
        'day' => '31',
    ]))->toBeNull()
        ->and(DateFieldValue::isValidCalendarDate([
            'year' => '2024',
            'month' => '2',
            'day' => '31',
        ]))->toBeFalse()
        ->and(DateFieldValue::isValidCalendarDate([
            'year' => '2024',
            'month' => '2',
            'day' => '29',
        ]))->toBeTrue();
});

it('normalizes calendar and datepicker request arrays into datetime values', function (): void {
    $calendarField = new Date([
        'handle' => 'calendarDate',
        'displayType' => 'calendar',
        'dateFormat' => 'd/m/Y',
        'timeFormat' => 'g:i A',
    ]);

    $calendarValue = $calendarField->normalizeValue([
        'date' => '10/02/2026',
        'time' => '2:00 AM',
    ], null);

    expect($calendarValue)->toBeInstanceOf(DateFieldValue::class)
        ->and($calendarValue->getPart('year'))->toBe('2026')
        ->and($calendarValue->getPart('month'))->toBe('2')
        ->and($calendarValue->getPart('day'))->toBe('10');

    $datePickerField = new Date([
        'handle' => 'pickerDate',
        'displayType' => 'datePicker',
    ]);

    $datePickerValue = $datePickerField->normalizeValue([
        'datetime' => '2026-02-10 14:00',
    ], null);

    expect($datePickerValue)->toBeInstanceOf(DateFieldValue::class)
        ->and($datePickerValue->getPart('year'))->toBe('2026')
        ->and($datePickerValue->getPart('month'))->toBe('2')
        ->and($datePickerValue->getPart('day'))->toBe('10')
        ->and($datePickerValue->getPart('hour'))->toBe('14')
        ->and($datePickerValue->getPart('minute'))->toBe('0');
});

it('renders preview html from wall-clock parts without timezone conversion', function (): void {
    $formatter = \Craft::$app->getFormatter();
    $originalTimeZone = $formatter->timeZone;
    $formatter->timeZone = 'Australia/Sydney';

    try {
        $field = new Date([
            'handle' => 'dateSigned',
            'displayType' => 'calendar',
            'dateFormat' => 'd/m/Y',
            'timeFormat' => 'g:i a',
        ]);

        $value = $field->normalizeValue([
            'year' => '2026',
            'month' => '7',
            'day' => '9',
            'hour' => '10',
            'minute' => '34',
            'second' => '21',
            'ampm' => 'AM',
        ], null);

        $html = $field->getPreviewHtml($value, new Submission());

        expect($html)->toContain('09/07/2026')
            ->and($html)->toContain('10:34');
    } finally {
        $formatter->timeZone = $originalTimeZone;
    }
});

it('exposes virtual date and time keys for ArrayHelper property access', function (): void {
    $value = new DateFieldValue([
        'year' => '2024',
        'month' => '6',
        'day' => '15',
        'hour' => '10',
        'minute' => '30',
        'second' => '0',
    ]);

    expect(isset($value->date))->toBeTrue()
        ->and($value->date)->toBe('2024-06-15')
        ->and($value->time)->toBe('10:30:00')
        ->and(ArrayHelper::getValue($value, 'date'))->toBe('2024-06-15')
        ->and(ArrayHelper::getValue(['eventDate' => $value], 'eventDate.date'))->toBe('2024-06-15');
});

it('resolves nested DateFieldValue paths through submission content accessor', function (): void {
    $accessor = new SubmissionContentAccessor();
    $value = new DateFieldValue([
        'year' => '2024',
        'month' => '6',
        'day' => '15',
        'hour' => '10',
        'minute' => '30',
    ]);

    expect($accessor->resolvePathValue($value, 'date'))->toBe('2024-06-15')
        ->and($accessor->resolvePathValue(['eventDate' => $value], 'eventDate.date'))->toBe('2024-06-15')
        ->and($accessor->resolvePathValue(['eventDate' => $value], 'eventDate.time'))->toBe('10:30:00')
        ->and($accessor->resolvePathValue(['eventDate' => $value], 'eventDate.year'))->toBe('2024');
});

it('validates and saves filled calendar date fields without undefined date property errors', function (): void {
    $form = formie()
        ->form(['title' => 'Calendar Date Submit #2923'])
        ->dateField('eventDate', [
            'displayType' => 'calendar',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $submission = formie()->submission($form)
        ->with([
            'eventDate' => [
                'date' => '2024-06-15',
                'time' => '10:30',
            ],
        ])
        ->save();

    $stored = $submission->getFieldValue('eventDate');

    expect($stored)->toBeInstanceOf(DateFieldValue::class)
        ->and($submission->getFieldValue('eventDate.date'))->toBe('2024-06-15')
        ->and($submission->getFieldValue('eventDate.time'))->not->toBe('')
        ->and($submission)->not->toHaveFieldError('eventDate')
        ->and($submission)->not->toHaveFieldError('eventDate.date');
});

it('validates and saves Group-nested calendar date fields without undefined date property errors', function (): void {
    $form = formie()
        ->form(['title' => 'Group Nested Date Submit #2923'])
        ->groupField('eventGroup', [
            'rows' => [[
                'fields' => [[
                    'type' => Date::class,
                    'handle' => 'eventDate',
                    'label' => 'Event Date',
                    'displayType' => 'calendar',
                    'dateFormat' => 'Y-m-d',
                    'timeFormat' => 'H:i',
                ]],
            ]],
        ])
        ->create();

    $submission = formie()->submission($form)
        ->with([
            'eventGroup' => [
                'eventDate' => [
                    'date' => '2024-06-15',
                    'time' => '10:30',
                ],
            ],
        ])
        ->save();

    expect($submission->getFieldValue('eventGroup.eventDate'))->toBeInstanceOf(DateFieldValue::class)
        ->and($submission->getFieldValue('eventGroup.eventDate.date'))->toBe('2024-06-15')
        ->and($submission)->not->toHaveFieldError('eventGroup.eventDate')
        ->and($submission)->not->toHaveFieldError('eventGroup.eventDate.date');
});
