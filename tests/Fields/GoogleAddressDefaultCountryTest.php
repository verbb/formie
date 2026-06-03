<?php

declare(strict_types=1);

use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\fields\subfields\AddressAutoComplete;

it('normalizes google places default country combobox values', function (): void {
    $config = [
        'countryDefaultValue' => ['value' => 'AU'],
    ];

    FieldConfigNormalizer::normalize($config, AddressAutoComplete::class);

    expect($config['countryDefaultValue'])->toBe('AU');
});
