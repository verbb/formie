<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

it('renders the correct calendar controls and initial values', function (string $displayType, bool $dateEnabled, bool $timeEnabled): void {
    $field = new Date([
        'handle' => 'eventDate',
        'displayType' => $displayType,
        'defaultOption' => 'date',
        'defaultValue' => '2024-06-15 14:30:00',
        'dateFormat' => 'd/m/Y',
        'timeFormat' => 'h:i A',
    ]);
    $field->setRows((new Date(['displayType' => $displayType]))->getSubFields());
    $field->getFieldByHandle('date')->enabled = $dateEnabled;
    $field->getFieldByHandle('time')->enabled = $timeEnabled;

    $form = new Form(['title' => 'Calendar Controls', 'handle' => 'calendarControls']);
    $form->setFormLayout(new FieldLayout([
        'pages' => [['rows' => [['fields' => [$field]]]]],
    ]));

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $inputs = (new DOMXPath($document))->query('//input[@data-formie-input]');

    if ($displayType === 'calendar') {
        $expected = [];

        if ($dateEnabled) {
            $expected[] = ['date', '2024-06-15', '[date]'];
        }

        if ($timeEnabled) {
            $expected[] = ['time', '14:30', '[time]'];
        }
    } else {
        $value = implode(' ', array_filter([
            $dateEnabled ? '15/06/2024' : null,
            $timeEnabled ? '02:30 PM' : null,
        ]));
        $expected = [['text', $value, '[datetime]']];
    }

    expect($inputs->length)->toBe(count($expected));

    foreach ($expected as $index => [$type, $value, $nameSuffix]) {
        $input = $inputs->item($index);

        expect($input->getAttribute('type'))->toBe($type)
            ->and($input->getAttribute('value'))->toBe($value)
            ->and($input->getAttribute('name'))->toEndWith($nameSuffix)
            ->and($input->hasAttribute('data-formie-date-datepicker-input'))->toBe($displayType === 'datePicker');
    }
})->with([
    'native date and time' => ['calendar', true, true],
    'native date only' => ['calendar', true, false],
    'native time only' => ['calendar', false, true],
    'picker date and time' => ['datePicker', true, true],
    'picker date only' => ['datePicker', true, false],
    'picker time only' => ['datePicker', false, true],
]);

it('renders min and max date limits on the native calendar date input', function (string $displayType, array $limits, ?string $min, ?string $max): void {
    $field = new Date(array_merge([
        'handle' => 'eventDate',
        'displayType' => $displayType,
    ], $limits));
    $field->setRows((new Date(['displayType' => $displayType]))->getSubFields());

    $form = new Form(['title' => 'Calendar Limits', 'handle' => 'calendarLimits']);
    $form->setFormLayout(new FieldLayout([
        'pages' => [['rows' => [['fields' => [$field]]]]],
    ]));

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $inputs = (new DOMXPath($document))->query('//input[@data-formie-input]');

    // "Today" limits resolve when the field renders, matching the server-side check.
    $min = $min === 'today' ? $field->getMinDate()->format('Y-m-d') : $min;
    $max = $max === 'today' ? $field->getMaxDate()->format('Y-m-d') : $max;

    foreach ($inputs as $input) {
        $isDateInput = $input->getAttribute('type') === 'date';

        expect($input->hasAttribute('min'))->toBe($isDateInput && $min !== null)
            ->and($input->hasAttribute('max'))->toBe($isDateInput && $max !== null);

        if ($isDateInput) {
            expect($input->getAttribute('min'))->toBe($min ?? '')
                ->and($input->getAttribute('max'))->toBe($max ?? '');
        }
    }

    expect($inputs->length)->toBeGreaterThan(0);
})->with([
    'calendar fixed limits' => ['calendar', [
        'minDateOption' => 'date',
        'minDate' => '2026-12-01',
        'maxDateOption' => 'date',
        'maxDate' => '2026-12-31',
    ], '2026-12-01', '2026-12-31'],
    'calendar today limits' => ['calendar', [
        'minDateOption' => 'today',
        'maxDateOption' => 'today',
        'maxDateOffsetNumber' => 7,
    ], 'today', 'today'],
    'calendar no limits' => ['calendar', [], null, null],
    'picker fixed limits' => ['datePicker', [
        'minDateOption' => 'date',
        'minDate' => '2026-12-01',
        'maxDateOption' => 'date',
        'maxDate' => '2026-12-31',
    ], null, null],
]);
