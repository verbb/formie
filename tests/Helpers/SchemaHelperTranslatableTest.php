<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;

it('returns schema unchanged when multi-site is disabled', function (): void {
    if (Craft::$app->getIsMultiSite()) {
        expect(true)->toBeTrue();

        return;
    }

    $schema = SchemaHelper::modalTabs([
        [
            'handle' => 'general',
            'label' => 'General',
            'content' => [SchemaHelper::labelField()],
        ],
    ]);

    $result = SchemaHelper::applyTranslatableToSchema($schema, ['label']);

    expect($result)->toBe($schema);
});

it('marks translatable properties inside modal tab schema roots', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        expect(true)->toBeTrue();

        return;
    }

    $field = Formie::$plugin->getFields()->getRegisteredFieldByType(SingleLineText::class);
    $schema = $field->getFormBuilderSchema();

    $result = SchemaHelper::applyTranslatableToSchema($schema, SingleLineText::translatableProperties());
    $json = json_encode($result);

    expect($json)->toContain('"translatable":true');
});

it('marks label fields inside a single modal tabs component node', function (): void {
    $schema = SchemaHelper::modalTabs([
        [
            'handle' => 'general',
            'label' => 'General',
            'content' => [
                SchemaHelper::labelField(['name' => 'label']),
                SchemaHelper::textField(['name' => 'placeholder']),
            ],
        ],
    ]);

    $method = new ReflectionMethod(SchemaHelper::class, '_applyTranslatableToSchemaNode');
    $method->setAccessible(true);

    $result = $method->invoke(null, $schema, ['label', 'placeholder']);

    $findByName = function (mixed $node, string $name) use (&$findByName): ?array {
        if (!is_array($node)) {
            return null;
        }

        if (array_is_list($node)) {
            foreach ($node as $child) {
                $match = $findByName($child, $name);

                if ($match !== null) {
                    return $match;
                }
            }

            return null;
        }

        if (($node['name'] ?? null) === $name) {
            return $node;
        }

        foreach (['children', 'schema'] as $key) {
            if (!isset($node[$key])) {
                continue;
            }

            $match = $findByName($node[$key], $name);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    };

    expect($findByName($result, 'label')['translatable'] ?? false)->toBeTrue()
        ->and($findByName($result, 'placeholder')['translatable'] ?? false)->toBeTrue();
});
