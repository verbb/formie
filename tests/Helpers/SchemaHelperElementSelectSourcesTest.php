<?php

declare(strict_types=1);

use craft\elements\Asset;
use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;

function findElementSelectField(array $nodes, string $name): ?array
{
    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }

        if (($node['$field'] ?? null) === 'elementSelect' && ($node['name'] ?? null) === $name) {
            return $node;
        }

        foreach (['children', 'schema'] as $key) {
            if (!isset($node[$key]) || !is_array($node[$key])) {
                continue;
            }

            $children = array_is_list($node[$key]) ? $node[$key] : [$node[$key]];
            $found = findElementSelectField($children, $name);

            if ($found) {
                return $found;
            }
        }
    }

    return null;
}

function normalizeElementSelectSources(mixed $sources): mixed
{
    $node = SchemaHelper::normalizeSchema(SchemaHelper::elementSelectField([
        'label' => 'Assets',
        'name' => 'assets',
        'config' => [
            'elementType' => Asset::class,
            'sources' => $sources,
        ],
    ]));

    return array_key_exists('sources', $node) ? $node['sources'] : 'missing';
}

it('lets Craft choose every source for the notification Attach Assets picker', function (): void {
    $schema = SchemaHelper::compileSchema(Formie::$plugin->getNotifications()->defineFormBuilderAdvancedSchema())['schema'];
    $field = findElementSelectField($schema, 'attachAssets');

    expect($field)->not->toBeNull()
        ->and($field['elementType'] ?? null)->toBe(Asset::class)
        ->and($field['sources'] ?? null)->toBeNull();
});

it('treats a wildcard source as unrestricted', function (): void {
    expect(normalizeElementSelectSources('*'))->toBeNull();
});

it('keeps explicit element select source restrictions', function (): void {
    expect(normalizeElementSelectSources(['volume:abc', 'volume:def']))->toBe(['volume:abc', 'volume:def'])
        ->and(normalizeElementSelectSources('volume:abc'))->toBe(['volume:abc']);
});
