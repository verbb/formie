<?php

declare(strict_types=1);

use Tests\fixtures\OptionSourceProviders\TestOptionsProvider;
use Tests\fixtures\OptionSourceProviders\TestRecipientsProvider;
use verbb\formie\events\RegisterOptionSourceProvidersEvent;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Recipients;
use verbb\formie\Formie;
use verbb\formie\models\OptionSource;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\resolvers\RegisteredOptionSourceResolver;
use verbb\formie\services\OptionSources;
use yii\base\Event;

beforeEach(function (): void {
    Formie::$plugin->getOptionSources()->clearRegisteredProviderClassesCache();
});

afterEach(function (): void {
    Event::off(OptionSources::class, OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS);
    Formie::$plugin->getOptionSources()->clearRegisteredProviderClassesCache();
});

function registerTestOptionSourceProviders(): void
{
    Event::on(OptionSources::class, OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS, function(RegisterOptionSourceProvidersEvent $event): void {
        $event->providers[] = TestOptionsProvider::class;
        $event->providers[] = TestRecipientsProvider::class;
    });
}

it('supports registered provider option sources through the resolver contract', function (): void {
    registerTestOptionSourceProviders();

    $resolver = new RegisteredOptionSourceResolver();

    expect($resolver->supports(OptionSource::fromConfig([
        'type' => 'provider',
        'provider' => 'test-options',
    ])))->toBeTrue()
        ->and($resolver->supports(OptionSource::fromConfig([
            'type' => 'predefined',
            'provider' => 'countries',
        ])))->toBeFalse();
});

it('filters registered providers by usage', function (): void {
    registerTestOptionSourceProviders();

    expect(OptionSourceProviderHelper::providerSupportsUsage('test-options', OptionSourceProviderHelper::USAGE_OPTIONS))->toBeTrue()
        ->and(OptionSourceProviderHelper::providerSupportsUsage('test-options', OptionSourceProviderHelper::USAGE_RECIPIENTS))->toBeFalse()
        ->and(OptionSourceProviderHelper::providerSupportsUsage('test-recipients', OptionSourceProviderHelper::USAGE_RECIPIENTS))->toBeTrue()
        ->and(OptionSourceProviderHelper::getProviderOptions(OptionSourceProviderHelper::USAGE_RECIPIENTS))->toHaveCount(1);
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('resolves registered option rows from provider params', function (): void {
    registerTestOptionSourceProviders();

    $result = OptionSourceProviderHelper::resolveOptions('test-options', [
        'group' => 'b',
    ]);

    expect($result->error)->toBeNull()
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[0]['value'])->toBe('b-1');
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('filters invalid recipient emails from registered provider rows', function (): void {
    registerTestOptionSourceProviders();

    $result = OptionSourceProviderHelper::resolveOptions(
        'test-recipients',
        [],
        null,
        OptionSourceProviderHelper::USAGE_RECIPIENTS,
    );

    expect($result->error)->toBeNull()
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[0]['value'])->toBe('sales@example.com');
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('retains registered provider option source config on options fields', function (): void {
    registerTestOptionSourceProviders();

    $field = new Dropdown([
        'optionsMode' => 'dynamic',
        'optionSource' => [
            'type' => 'provider',
            'provider' => 'test-options',
            'params' => [
                'group' => 'a',
            ],
        ],
    ]);

    expect($field->getOptionSource()?->type)->toBe('provider')
        ->and($field->getOptionSource()?->provider)->toBe('test-options')
        ->and($field->getOptionSource()?->params['group'])->toBe('a');
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('allows registered recipient providers on recipients fields', function (): void {
    registerTestOptionSourceProviders();

    $field = new Recipients([
        'displayType' => 'dropdown',
        'optionsMode' => 'dynamic',
        'optionSource' => [
            'type' => 'provider',
            'provider' => 'test-recipients',
            'params' => [],
        ],
    ]);

    expect($field->getOptionsMode())->toBe('dynamic')
        ->and($field->getOptionSource()?->provider)->toBe('test-recipients')
        ->and($field->getResolvedOptions())->toHaveCount(2)
        ->and($field->getResolvedOptions()[0]['value'])->toBe('sales@example.com');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !Formie::$plugin, 'Requires Craft bootstrap');

it('rejects registered providers that do not support recipients usage', function (): void {
    registerTestOptionSourceProviders();

    $field = new Recipients([
        'displayType' => 'dropdown',
        'optionsMode' => 'dynamic',
        'optionSource' => [
            'type' => 'provider',
            'provider' => 'test-options',
            'params' => [
                'group' => 'a',
            ],
        ],
    ]);

    expect($field->getOptionsMode())->toBe('static')
        ->and($field->getOptionSource())->toBeNull();
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');
