<?php

declare(strict_types=1);

use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use verbb\formie\fields\Categories;
use verbb\formie\fields\Entries;
use verbb\formie\fields\Tags;
use verbb\formie\fields\Users;

function relationContractFields(): array
{
    return [
        new Entries(['handle' => 'entriesRel', 'sources' => ['*']]),
        new Users(['handle' => 'usersRel']),
        new Categories(['handle' => 'categoriesRel']),
        new Tags(['handle' => 'tagsRel']),
    ];
}

function seededRelationFixtures(): array
{
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    $user = User::find()->status(null)->username('formie-seed-user')->one();
    $tag = Tag::find()->status(null)->title('Formie Seed Tag')->one();
    $category = Category::find()->status(null)->title('Formie Seed Category')->one();

    if (!$entry || !$user || !$tag || !$category) {
        throw new RuntimeException('Missing seeded relation elements. Run `composer test:setup`.');
    }

    return [
        [new Entries(['handle' => 'entriesRel', 'sources' => ['*']]), $entry->id, (string)$entry->title],
        [new Users(['handle' => 'usersRel']), $user->id, 'formie-seed-user@example.test'],
        [new Tags(['handle' => 'tagsRel']), $tag->id, 'Formie Seed Tag'],
        [new Categories(['handle' => 'categoriesRel']), $category->id, 'Formie Seed Category'],
    ];
}

it('normalizes relation id payloads into fixed-order element queries', function (): void {
    foreach (relationContractFields() as $field) {
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

it('resolves seeded relation elements through projection wrappers', function (): void {
    foreach (seededRelationFixtures() as [$field, $id, $expectedString]) {
        $value = $field->normalizeValue([$id], null);
        $json = $field->getValueAsArray($value, null);

        expect($field->serializeValue($value, null))->toBe([$id])
            ->and($field->getValueAsString($value, null))->toBe($expectedString)
            ->and($field->getValueForExport($value, null))->toBe($expectedString)
            ->and((string)$field->getValueForSummary($value, null))->toBe($expectedString)
            ->and($json)->toBeArray()
            ->and($json)->not->toBeEmpty();
    }
});

it('keeps unresolved relation projection wrappers deterministic', function (): void {
    foreach (relationContractFields() as $field) {
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

