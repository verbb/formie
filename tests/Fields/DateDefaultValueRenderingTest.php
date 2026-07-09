<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('renders calendar date and time sub-field values from the parent default', function (): void {
    $form = formie()
        ->form(['title' => 'Date Default Value Render'])
        ->dateField('eventDate', [
            'displayType' => 'calendar',
            'defaultOption' => 'date',
            'defaultValue' => '2024-06-15 14:30:00',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');

    expect($html)
        ->toContain('value="2024-06-15"')
        ->toContain('value="14:30"');
});

it('renders date picker default values from the parent default', function (): void {
    $form = formie()
        ->form(['title' => 'Date Picker Default Value Render'])
        ->dateField('eventDate', [
            'displayType' => 'datePicker',
            'defaultOption' => 'date',
            'defaultValue' => '2024-06-15 14:30:00',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'eventDate');

    expect($html)->toContain('value="2024-06-15 14:30"');
});

it('resolves date sub-field initial values as scalar parts', function (): void {
    $form = formie()
        ->form(['title' => 'Date Sub-field Initial Values'])
        ->dateField('eventDate', [
            'displayType' => 'calendar',
            'defaultOption' => 'date',
            'defaultValue' => '2024-06-15 14:30:00',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $field = $form->getFieldByHandle('eventDate');
    $dateField = $field->getFieldByHandle('date');
    $timeField = $field->getFieldByHandle('time');

    expect($dateField->getInitialValue())->toBe('2024-06-15')
        ->and($timeField->getInitialValue())->toBe('14:30');
});
