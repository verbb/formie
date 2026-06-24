<?php

declare(strict_types=1);

use verbb\formie\integrations\emailmarketing\Brevo;
use verbb\formie\models\IntegrationField;

it('includes a mappable list field in Brevo integration settings', function (): void {
    $integration = new Brevo(['name' => 'Brevo', 'handle' => 'brevo']);
    $method = new ReflectionMethod($integration, '_getListIntegrationField');
    $method->setAccessible(true);

    $field = $method->invoke($integration, [
        ['id' => 12, 'name' => 'Newsletter'],
        ['id' => 34, 'name' => 'Events'],
    ]);

    expect($field)->toBeInstanceOf(IntegrationField::class)
        ->and($field->handle)->toBe('listId')
        ->and($field->options['options'])->toBe([
            ['label' => 'Newsletter', 'value' => '12'],
            ['label' => 'Events', 'value' => '34'],
        ]);
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('resolves Brevo list ids from mapped values with static fallback', function (): void {
    $integration = new Brevo([
        'name' => 'Brevo',
        'handle' => 'brevo',
        'listId' => '99',
    ]);
    $method = new ReflectionMethod($integration, '_resolveListIds');
    $method->setAccessible(true);

    expect($method->invoke($integration, null))->toBe([99])
        ->and($method->invoke($integration, '12'))->toBe([12])
        ->and($method->invoke($integration, '12, 34'))->toBe([12, 34])
        ->and($method->invoke($integration, ['12', '34', '12']))->toBe([12, 34]);
});

it('prefers mapped Brevo list ids over the static integration list', function (): void {
    $integration = new Brevo([
        'name' => 'Brevo',
        'handle' => 'brevo',
        'listId' => '99',
    ]);
    $method = new ReflectionMethod($integration, '_resolveListIds');
    $method->setAccessible(true);

    expect($method->invoke($integration, '12'))->toBe([12])
        ->and($method->invoke($integration, ''))->toBe([99]);
});

it('returns no Brevo list ids when nothing is configured or mapped', function (): void {
    $integration = new Brevo(['name' => 'Brevo', 'handle' => 'brevo']);
    $method = new ReflectionMethod($integration, '_resolveListIds');
    $method->setAccessible(true);

    expect($method->invoke($integration, null))->toBe([])
        ->and($method->invoke($integration, ''))->toBe([])
        ->and($method->invoke($integration, '0, abc'))->toBe([]);
});
