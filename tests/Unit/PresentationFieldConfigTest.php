<?php

declare(strict_types=1);

use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Radio;
use verbb\formie\fields\traits\PresentationFieldConfigTrait;

final class PresentationFieldConfigTestHost
{
    use PresentationFieldConfigTrait {
        filterPresentationFieldConfig as public;
        createPresentationField as public;
    }
}

it('filters wrapper-only settings from radio presentation fields', function (): void {
    $host = new PresentationFieldConfigTestHost();

    $config = $host->filterPresentationFieldConfig(Radio::class, [
        'handle' => 'category',
        'useSearchable' => true,
        'options' => [
            ['label' => 'One', 'value' => 'one'],
        ],
        'layout' => 'vertical',
        'hasMultiNamespace' => true,
        'multi' => false,
    ]);

    expect($config)->toHaveKey('handle')
        ->and($config)->toHaveKey('options')
        ->and($config)->toHaveKey('layout')
        ->and($config)->toHaveKey('hasMultiNamespace')
        ->and($config)->not->toHaveKey('useSearchable');
});

it('preserves useSearchable for dropdown presentation fields', function (): void {
    $host = new PresentationFieldConfigTestHost();

    $config = $host->filterPresentationFieldConfig(Dropdown::class, [
        'handle' => 'category',
        'useSearchable' => true,
        'options' => [
            ['label' => 'One', 'value' => 'one'],
        ],
    ]);

    expect($config['useSearchable'] ?? null)->toBeTrue();
});

it('filters options from hidden presentation fields', function (): void {
    $host = new PresentationFieldConfigTestHost();

    $config = $host->filterPresentationFieldConfig(Hidden::class, [
        'handle' => 'recipients',
        'useSearchable' => false,
        'options' => [
            ['label' => 'Hidden', 'value' => 'hidden@example.com'],
        ],
    ]);

    expect($config)->toHaveKey('handle')
        ->and($config)->not->toHaveKey('options')
        ->and($config)->not->toHaveKey('useSearchable');
});

it('creates checkboxes presentation fields without unknown property errors', function (): void {
    $host = new PresentationFieldConfigTestHost();

    $field = $host->createPresentationField(Checkboxes::class, [
        'handle' => 'categories',
        'useSearchable' => false,
        'options' => [
            ['label' => 'News', 'value' => '1'],
        ],
        'layout' => 'vertical',
    ]);

    expect($field)->toBeInstanceOf(Checkboxes::class)
        ->and($field->handle)->toBe('categories');
});
