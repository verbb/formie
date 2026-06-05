<?php

declare(strict_types=1);

use Craft;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\services\FieldPalette;

it('builds default palette groups from registered pickable field types', function (): void {
    $palette = Formie::$plugin->getFieldPalette()->getResolvedPalette();

    expect($palette['groups'] ?? null)->toBeArray()->not->toBeEmpty();

    $fieldClasses = [];

    foreach ($palette['groups'] as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            $fieldClasses[] = $field['fieldClass'];
        }
    }

    foreach ($palette['unassigned'] ?? [] as $field) {
        $fieldClasses[] = $field['fieldClass'];
    }

    expect($fieldClasses)->toContain(SingleLineText::class)
        ->and($fieldClasses)->toContain(Dropdown::class);
});

it('respects disabled palette entries when resolving registered field types', function (): void {
    $service = Formie::$plugin->getFieldPalette();
    $palette = $service->getResolvedPalette();
    $targetClass = SingleLineText::class;

    $saved = false;

    foreach ($palette['groups'] as &$group) {
        foreach ($group['fields'] as &$field) {
            if (($field['fieldClass'] ?? null) === $targetClass) {
                $field['enabled'] = false;
                $saved = true;
            }
        }
    }
    unset($group, $field);

    expect($saved)->toBeTrue();

    $service->savePalette($palette);

    $enabledClasses = Formie::$plugin->getFields()->getResolvedRegisteredFieldTypes(true);

    expect($enabledClasses)->not->toContain($targetClass);

    $groups = $service->buildFormBuilderFieldTypeGroups();
    $builderClasses = [];

    foreach ($groups as $group) {
        foreach ($group['fields'] as $fieldConfig) {
            $builderClasses[] = $fieldConfig['type'];
        }
    }

    expect($builderClasses)->not->toContain($targetClass);
});

it('applies palette label overrides to form builder field type groups', function (): void {
    $service = Formie::$plugin->getFieldPalette();
    $palette = $service->getResolvedPalette();
    $targetClass = Dropdown::class;
    $overrideLabel = 'Select';
    $updated = false;

    foreach ($palette['groups'] as &$group) {
        foreach ($group['fields'] as &$field) {
            if (($field['fieldClass'] ?? null) === $targetClass) {
                $field['label'] = $overrideLabel;
                $field['enabled'] = true;
                $updated = true;
            }
        }
    }
    unset($group, $field);

    expect($updated)->toBeTrue();

    $service->savePalette($palette);

    $groups = $service->buildFormBuilderFieldTypeGroups();
    $labels = [];

    foreach ($groups as $group) {
        foreach ($group['fields'] as $fieldConfig) {
            if (($fieldConfig['type'] ?? null) === $targetClass) {
                $labels[] = $fieldConfig['label'];
            }
        }
    }

    expect($labels)->toContain($overrideLabel);
});

afterEach(function (): void {
    Craft::$app->getProjectConfig()->remove(FieldPalette::CONFIG_KEY);
    Formie::$plugin->getFields()->resetFieldRegistryCache();
});
