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
