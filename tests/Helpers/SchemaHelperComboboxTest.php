<?php

declare(strict_types=1);

use verbb\formie\helpers\SchemaHelper;
use verbb\formie\integrations\payments\Stripe;

function findComboboxField(array $nodes, string $name): ?array
{
    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }

        if (($node['$field'] ?? null) === 'combobox' && ($node['name'] ?? null) === $name) {
            return $node;
        }

        foreach (['children', 'schema'] as $key) {
            if (!isset($node[$key]) || !is_array($node[$key])) {
                continue;
            }

            $children = array_is_list($node[$key]) ? $node[$key] : [$node[$key]];
            $found = findComboboxField($children, $name);

            if ($found) {
                return $found;
            }
        }
    }

    return null;
}

it('defines Stripe currency combobox placeholders instead of empty options', function (): void {
    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $schema = SchemaHelper::compileSchema($integration->defineFormBuilderGeneralSchema())['schema'];
    $currencyField = findComboboxField($schema, 'currencyFixed');

    expect($currencyField)->not->toBeNull()
        ->and($currencyField['placeholder'] ?? null)->toBe('Select an option');

    $emptyOptions = array_filter(
        $currencyField['options'] ?? [],
        fn(array $option) => ($option['value'] ?? null) === ''
    );

    expect($emptyOptions)->toBeEmpty();
});
