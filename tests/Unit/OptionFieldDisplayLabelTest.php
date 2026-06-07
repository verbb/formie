<?php

declare(strict_types=1);

use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;

it('uses option value when display label is missing on OptionValue', function (): void {
    $option = new OptionValue(null, '42-Artist Name', true, false);

    expect($option->getDisplayLabel())->toBe('42-Artist Name');
});

it('uses option value when display label is missing on SingleOptionFieldValue', function (): void {
    $value = new SingleOptionFieldValue(null, 'support', true, false);

    expect($value->getDisplayLabel())->toBe('support');
});

it('uses option values when display labels are missing on MultiOptionFieldValue', function (): void {
    $value = new MultiOptionFieldValue([
        new OptionValue(null, 'one', true, false),
        new OptionValue('Two', 'two', true, true),
    ]);

    expect($value->labels())->toBe(['one', 'Two']);
});
