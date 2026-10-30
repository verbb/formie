<?php

declare(strict_types=1);

use verbb\formie\fields\Dropdown;
use verbb\formie\fields\subfields\DateHourDropdown;
use verbb\formie\fields\values\SingleOptionFieldValue;

it('serializes raw scalar option values for the client definition', function (): void {
    // Date dropdown parts hand their initial value over as a plain scalar, '' when nothing is set
    $field = new DateHourDropdown([
        'handle' => 'hour',
        'label' => 'Hour',
    ]);

    expect($field->serializeValueForClientInput(''))->toBeNull()
        ->and($field->serializeValueForClientInput(null))->toBeNull()
        ->and($field->serializeValueForClientInput('15'))->toBe('15')
        ->and($field->serializeValueForClientInput(15))->toBe(15)
        ->and($field->serializeValueForClientInput(['9', '10']))->toBe(['9', '10']);
});

it('serializes option value objects for the client definition', function (): void {
    $field = new Dropdown([
        'handle' => 'size',
        'label' => 'Size',
    ]);

    expect($field->serializeValueForClientInput(new SingleOptionFieldValue('Large', 'l', true)))->toBe('l');
});
