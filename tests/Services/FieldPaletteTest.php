<?php

declare(strict_types=1);

use Craft;
use verbb\formie\fields\Content;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Html;
use verbb\formie\fields\Note;
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

it('places new field types in their default palette groups instead of unassigned', function (): void {
    $palette = Formie::$plugin->getFieldPalette()->getResolvedPalette();
    $cosmeticGroup = null;

    foreach ($palette['groups'] ?? [] as $group) {
        if (($group['handle'] ?? null) === 'cosmetic') {
            $cosmeticGroup = $group;
            break;
        }
    }

    expect($cosmeticGroup)->not->toBeNull();

    $cosmeticFieldClasses = array_map(
        static fn(array $field): string => $field['fieldClass'],
        $cosmeticGroup['fields'] ?? [],
    );

    expect($cosmeticFieldClasses)->toContain(Content::class)
        ->and($cosmeticFieldClasses)->toContain(Note::class);

    $unassignedFieldClasses = array_map(
        static fn(array $field): string => $field['fieldClass'],
        $palette['unassigned'] ?? [],
    );

    expect($unassignedFieldClasses)->not->toContain(Content::class);
});

it('keeps html and rich text adjacent in the cosmetic palette group', function (): void {
    $service = Formie::$plugin->getFieldPalette();

    Craft::$app->getProjectConfig()->set(FieldPalette::CONFIG_KEY, [
        'version' => FieldPalette::VERSION,
        'groups' => [
            [
                'uid' => 'cosmetic-group',
                'handle' => 'cosmetic',
                'name' => 'Cosmetic Fields',
                'fields' => [
                    ['fieldClass' => verbb\formie\fields\Heading::class, 'enabled' => true, 'label' => null],
                    ['fieldClass' => verbb\formie\fields\Section::class, 'enabled' => true, 'label' => null],
                    ['fieldClass' => Html::class, 'enabled' => true, 'label' => null],
                    ['fieldClass' => verbb\formie\fields\Summary::class, 'enabled' => true, 'label' => null],
                    ['fieldClass' => Content::class, 'enabled' => true, 'label' => null],
                    ['fieldClass' => Note::class, 'enabled' => true, 'label' => null],
                ],
            ],
        ],
        'unassigned' => [],
    ], 'Test cosmetic field order');

    $palette = $service->getResolvedPalette();
    $cosmeticFieldClasses = [];

    foreach ($palette['groups'] ?? [] as $group) {
        if (($group['handle'] ?? null) !== 'cosmetic') {
            continue;
        }

        $cosmeticFieldClasses = array_map(
            static fn(array $field): string => $field['fieldClass'],
            $group['fields'] ?? [],
        );
    }

    $htmlIndex = array_search(Html::class, $cosmeticFieldClasses, true);
    $contentIndex = array_search(Content::class, $cosmeticFieldClasses, true);

    expect($htmlIndex)->not->toBeFalse()
        ->and($contentIndex)->not->toBeFalse()
        ->and($contentIndex)->toBe($htmlIndex + 1);
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
