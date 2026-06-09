<?php

declare(strict_types=1);

use verbb\formie\options\IntegrationOptionSourceHelper;
use verbb\formie\options\resolvers\IntegrationOptionSourceResolver;
use verbb\formie\models\OptionSource;

it('flattens nested integration field options', function (): void {
    $rows = IntegrationOptionSourceHelper::flattenIntegrationFieldOptions([
        [
            'label' => 'Interests',
            'options' => [
                ['label' => 'Group A - One', 'value' => '1'],
                ['label' => 'Group A - Two', 'value' => '2'],
            ],
        ],
        ['label' => 'Flat', 'value' => 'flat'],
    ]);

    expect($rows)->toHaveCount(3)
        ->and($rows[0]['value'])->toBe('1')
        ->and($rows[2]['label'])->toBe('Flat');
});

it('flattens a persisted integration option group', function (): void {
    $rows = IntegrationOptionSourceHelper::flattenIntegrationFieldOptions([
        'label' => 'Tags',
        'options' => [
            ['label' => 'Mailchimp', 'value' => 'Mailchimp'],
            ['label' => 'Plugin Option', 'value' => 'Plugin Option'],
        ],
    ]);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['label'])->toBe('Mailchimp')
        ->and($rows[1]['value'])->toBe('Plugin Option');
});

it('supports integration option sources through the resolver contract', function (): void {
    $resolver = new IntegrationOptionSourceResolver();

    expect($resolver->supports(OptionSource::fromConfig([
        'type' => 'integration',
        'provider' => 'mailchimp-interests',
    ])))->toBeTrue()
        ->and($resolver->supports(OptionSource::fromConfig([
            'type' => 'predefined',
            'provider' => 'countries',
        ])))->toBeFalse();
});

it('requires integration params before resolving', function (): void {
    $result = IntegrationOptionSourceHelper::resolveOptions('mailchimp-interests', []);

    expect($result->error)->not->toBeNull()
        ->and($result->items)->toBe([]);
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('retains integration option source config on options fields', function (): void {
    $field = new verbb\formie\fields\Dropdown([
        'optionsMode' => 'dynamic',
        'optionSource' => [
            'type' => 'integration',
            'provider' => 'mailchimp-interests',
            'params' => [
                'integrationId' => 1,
                'collectionId' => 'abc123',
                'remoteHandle' => 'interestCategories',
            ],
        ],
    ]);

    expect($field->getOptionSource()?->type)->toBe('integration')
        ->and($field->getOptionSource()?->provider)->toBe('mailchimp-interests')
        ->and($field->getOptionSource()?->params['collectionId'])->toBe('abc123');
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('allows only integration dynamic sources on recipients fields', function (): void {
    $integrationField = new verbb\formie\fields\Recipients([
        'displayType' => 'dropdown',
        'optionsMode' => 'dynamic',
        'optionSource' => [
            'type' => 'integration',
            'provider' => 'mailchimp-interests',
            'params' => [
                'integrationId' => 1,
            ],
        ],
    ]);

    $predefinedField = new verbb\formie\fields\Recipients([
        'displayType' => 'dropdown',
        'optionsMode' => 'dynamic',
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
        ],
    ]);

    expect($integrationField->getOptionsMode())->toBe('dynamic')
        ->and($integrationField->getOptionSource()?->type)->toBe('integration')
        ->and($predefinedField->getOptionsMode())->toBe('static')
        ->and($predefinedField->getOptionSource())->toBeNull();
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('resolves dynamic recipient options while keeping front-end obfuscation', function (): void {
    $field = new verbb\formie\fields\Recipients([
        'displayType' => 'dropdown',
        'optionsMode' => verbb\formie\helpers\OptionsMode::STATIC,
        'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.com'],
        ],
    ]);

    expect($field->getResolvedOptions()[0]['value'])->toBe('sales@example.com')
        ->and($field->getFieldOptions()[0]['value'])->toStartWith('base64:')
        ->and($field->getRealValue($field->getFieldOptions()[0]['value']))->toBe('sales@example.com')
        ->and($field->getOptionsMode())->toBe('static');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !\verbb\formie\Formie::$plugin, 'Requires Craft bootstrap');
