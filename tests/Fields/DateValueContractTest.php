<?php

declare(strict_types=1);

use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\fields\Date;

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
