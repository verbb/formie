<?php

declare(strict_types=1);

use verbb\formie\fields\Products;
use verbb\formie\fields\Variants;
use verbb\formie\helpers\Plugin;

function commerceRelationContractFields(): array
{
    if (!class_exists(Products::class) || !class_exists(Variants::class)) {
        return [];
    }

    return [
        new Products(['handle' => 'productsRel']),
        new Variants(['handle' => 'variantsRel']),
    ];
}

it('normalizes commerce relation id payloads into fixed-order element queries', function (): void {
    if (!Plugin::isPluginInstalledAndEnabled('commerce')) {
        $this->markTestSkipped('Commerce plugin is not installed/enabled for this environment.');
    }

    foreach (commerceRelationContractFields() as $field) {
        $fromIdMaps = $field->normalizeValue([
            ['id' => '7'],
            ['id' => ''],
            ['id' => '12'],
        ], null);

        $fromScalarIds = $field->normalizeValue(['7', '', '12'], null);

        expect($fromIdMaps->id)->toBe(['7', '12'])
            ->and($fromIdMaps->fixedOrder)->toBeTrue()
            ->and($fromScalarIds->id)->toBe(['7', '12'])
            ->and($fromScalarIds->fixedOrder)->toBeTrue();
    }
});

it('keeps unresolved commerce relation projections deterministic', function (): void {
    if (!Plugin::isPluginInstalledAndEnabled('commerce')) {
        $this->markTestSkipped('Commerce plugin is not installed/enabled for this environment.');
    }

    foreach (commerceRelationContractFields() as $field) {
        $value = $field->normalizeValue([
            ['id' => '7'],
            ['id' => '12'],
        ], null);

        expect($field->serializeValue($value, null))->toBe([])
            ->and($field->getValueAsString($value, null))->toBe('')
            ->and($field->getValueAsArray($value, null))->toBe([])
            ->and($field->getValueForExport($value, null))->toBe('')
            ->and((string)$field->getValueForSummary($value, null))->toBe('');
    }
});
