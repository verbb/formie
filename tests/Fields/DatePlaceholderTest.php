<?php

declare(strict_types=1);

it('uses translatable format placeholders for date picker fields without a custom placeholder', function (): void {
    $form = formie()
        ->form(['title' => 'Date Placeholder Translation'])
        ->dateField('eventDate', [
            'displayType' => 'datePicker',
            'dateFormat' => 'd.m.Y',
            'timeFormat' => 'H:i',
        ])
        ->create();

    $field = $form->getFieldByHandle('eventDate');

    expect($field->getEffectivePlaceholder())->toBe('DD.MM.YYYY 23:59 (HH:MM)');
});

it('prefers a custom placeholder over the generated format placeholder', function (): void {
    $form = formie()
        ->form(['title' => 'Date Custom Placeholder'])
        ->dateField('eventDate', [
            'displayType' => 'datePicker',
            'dateFormat' => 'd.m.Y',
            'placeholder' => 'Pick a date',
        ])
        ->create();

    $field = $form->getFieldByHandle('eventDate');

    expect($field->getEffectivePlaceholder())->toBe('Pick a date');
});

it('does not generate format placeholders for native calendar fields', function (): void {
    $form = formie()
        ->form(['title' => 'Date Native Placeholder'])
        ->dateField('eventDate', [
            'displayType' => 'calendar',
            'dateFormat' => 'd.m.Y',
        ])
        ->create();

    $field = $form->getFieldByHandle('eventDate');

    expect($field->getEffectivePlaceholder())->toBeNull();
});
