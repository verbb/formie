<?php

declare(strict_types=1);

use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Recipients;

it('normalizes legacy recipients multi setting to multiple', function (): void {
    $config = [
        'displayType' => 'dropdown',
        'multi' => false,
        'options' => [],
    ];

    FieldConfigNormalizer::normalize($config, Recipients::class);

    expect($config)->not->toHaveKey('multi')
        ->and($config['multiple'])->toBeFalse();
});

it('preserves existing recipients multiple setting when legacy multi is present', function (): void {
    $config = [
        'displayType' => 'dropdown',
        'multi' => true,
        'multiple' => false,
        'options' => [],
    ];

    FieldConfigNormalizer::normalize($config, Recipients::class);

    expect($config)->not->toHaveKey('multi')
        ->and($config['multiple'])->toBeFalse();
});

it('does not remap multi for non-recipients fields', function (): void {
    $config = [
        'multi' => true,
        'options' => [],
    ];

    FieldConfigNormalizer::normalize($config, Dropdown::class);

    expect($config['multi'])->toBeTrue()
        ->and($config)->not->toHaveKey('multiple');
});
