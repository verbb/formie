<?php

declare(strict_types=1);

use verbb\formie\fields\Dropdown;
use verbb\formie\Formie;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\models\OptionSource;
use verbb\formie\options\OptionSourceValidationMode;
use verbb\formie\options\resolvers\PredefinedOptionSourceResolver;

it('resolves countries through the predefined option source resolver', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
            'params' => [
                'labelKey' => 'name',
                'valueKey' => '2-letter',
            ],
        ],
    ]);

    $rows = Formie::$plugin->getOptionSources()->resolveRows($field);

    expect($rows)->not->toBeEmpty()
        ->and($rows[0]['label'] ?? null)->not->toBe('')
        ->and($rows[0]['value'] ?? null)->toHaveLength(2);
});

it('resolves simple string-list predefined option sources', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'acceptability',
        ],
    ]);

    $rows = Formie::$plugin->getOptionSources()->resolveRows($field);

    expect($rows)->not->toBeEmpty()
        ->and($rows[0]['label'])->toBe('Acceptable')
        ->and($rows[0]['value'])->toBe('Acceptable');
});

it('treats predefined dynamic option sources as strict validation sources', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
            'params' => [
                'labelKey' => 'name',
                'valueKey' => '2-letter',
            ],
        ],
    ]);

    expect(Formie::$plugin->getOptionSources()->getValidationMode($field))->toBe(OptionSourceValidationMode::STRICT);
});

it('normalizes option source validation policy names', function (): void {
    $resolver = new PredefinedOptionSourceResolver();

    expect($resolver->validationMode(OptionSource::fromConfig([
        'type' => 'predefined',
        'provider' => 'countries',
    ])))->toBe(OptionSourceValidationMode::STRICT)
        ->and(OptionSourceValidationMode::normalize('unknown'))->toBe(OptionSourceValidationMode::STRICT)
        ->and(OptionSourceValidationMode::normalize(OptionSourceValidationMode::ACCEPT_SUBMITTED))->toBe(OptionSourceValidationMode::ACCEPT_SUBMITTED);
});
