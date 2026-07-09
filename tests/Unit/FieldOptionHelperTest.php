<?php

declare(strict_types=1);

use verbb\formie\helpers\FieldOptionHelper;

it('normalizes legacy recipient placeholder options', function (): void {
    $options = FieldOptionHelper::sanitizeRecipientPlaceholderOptions([
        [
            'label' => 'Click here to select a contact ...',
            'value' => 'Click here to select a contact ...',
            'isDefault' => true,
        ],
        [
            'label' => 'QLD: Far North',
            'value' => 'vmtc.seq@gmail.com',
            'isDefault' => true,
        ],
    ]);

    expect($options[0]['value'])->toBe('')
        ->and($options[0]['default'])->toBeFalse()
        ->and($options[1]['default'])->toBeFalse()
        ->and($options[1]['isDefault'] ?? null)->toBeNull();
});
