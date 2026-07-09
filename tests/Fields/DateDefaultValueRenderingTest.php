<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

function createDateFieldWithDefaults(array $fieldConfig = []): Date
{
    $field = new Date(array_merge([
        'handle' => 'eventDate',
        'displayType' => 'calendar',
        'defaultOption' => 'date',
        'defaultValue' => '2024-06-15 14:30:00',
        'dateFormat' => 'Y-m-d',
        'timeFormat' => 'H:i',
        'label' => 'Event Date',
    ], $fieldConfig));

    $field->setRows((new Date(['displayType' => $field->displayType]))->getSubFields());

    return $field;
}

function createFormWithField(Date $field): Form
{
    $form = new Form([
        'title' => 'Date Default Value Render',
        'handle' => 'dateDefaultValueRender',
    ]);

    $form->setFormLayout(new FieldLayout([
        'pages' => [[
            'rows' => [[
                'fields' => [$field],
            ]],
        ]],
    ]));

    return $form;
}

it('renders calendar date and time sub-field values from the parent default', function (): void {
    $form = createFormWithField(createDateFieldWithDefaults([
        'displayType' => 'calendar',
    ]));

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');

    expect($html)
        ->toContain('value="2024-06-15"')
        ->toContain('value="14:30"');
});

it('renders date picker default values from the parent default', function (): void {
    $form = createFormWithField(createDateFieldWithDefaults([
        'displayType' => 'datePicker',
    ]));

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');

    expect($html)->toContain('value="2024-06-15 14:30"');
});

it('resolves date sub-field initial values as scalar parts', function (): void {
    $field = createDateFieldWithDefaults([
        'displayType' => 'calendar',
    ]);

    $dateField = $field->getFieldByHandle('date');
    $timeField = $field->getFieldByHandle('time');

    expect($dateField->getInitialValue())->toBe('2024-06-15')
        ->and($timeField->getInitialValue())->toBe('14:30');
});

it('uses the current time for today defaults on date/time fields', function (): void {
    $field = createDateFieldWithDefaults([
        'defaultOption' => 'today',
        'defaultValue' => null,
    ]);

    $now = new DateTime();
    $defaultValue = $field->getDefaultValue();
    $dateTime = DateFieldValue::toDateTime($defaultValue);

    expect($dateTime)->toBeInstanceOf(DateTime::class)
        ->and($dateTime->format('Y-m-d'))->toBe($now->format('Y-m-d'))
        ->and($dateTime->format('H:i'))->toBe($now->format('H:i'));

    $timeField = $field->getFieldByHandle('time');

    expect($timeField->getInitialValue())->toBe($now->format('H:i'));
});

it('uses midnight for today defaults on date-only fields', function (): void {
    $field = createDateFieldWithDefaults([
        'defaultOption' => 'today',
        'defaultValue' => null,
        'displayType' => 'datePicker',
    ]);

    $timeSubField = $field->getFieldByHandle('time');
    $timeSubField->enabled = false;

    $dateTime = DateFieldValue::toDateTime($field->getDefaultValue());

    expect($dateTime)->toBeInstanceOf(DateTime::class)
        ->and($dateTime->format('Y-m-d'))->toBe((new DateTime('today'))->format('Y-m-d'))
        ->and($dateTime->format('H:i:s'))->toBe('00:00:00');
});
