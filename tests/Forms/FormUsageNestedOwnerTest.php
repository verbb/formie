<?php

declare(strict_types=1);

use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\Entry;
use craft\elements\User;
use craft\elements\db\ElementQuery;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\ContentBlock;
use craft\fields\Matrix;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\formie\fields\Forms as FormsField;
use verbb\formie\Formie;
use yii\base\Event;

beforeEach(function(): void {
    $this->formUsageNestedFixtures = [];
    Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
});

afterEach(function(): void {
    foreach ($this->formUsageNestedFixtures as $fixture) {
        foreach ($fixture['entries'] as $entry) {
            Craft::$app->getElements()->deleteElement($entry, true);
        }

        Craft::$app->getEntries()->deleteSection($fixture['section']);
        Craft::$app->getEntries()->deleteEntryType($fixture['pageType']);
        Craft::$app->getFields()->deleteField($fixture['block']);
        Craft::$app->getFields()->deleteField($fixture['matrix']);
        Craft::$app->getEntries()->deleteEntryType($fixture['nestedType']);
        Craft::$app->getFields()->deleteField($fixture['formsField']);
    }

    Craft::$app->getUser()->setIdentity(null);
});

function formUsageNestedLayout(string $type, array $elements): FieldLayout
{
    $layout = new FieldLayout(['type' => $type]);
    $tab = new FieldLayoutTab(['name' => 'Content']);
    $tab->setLayout($layout);
    $tab->setElements($elements);
    $layout->setTabs([$tab]);

    return $layout;
}

/**
 * Creates a section whose entries hold a Forms field in a Content Block field and in a Matrix field.
 */
function formUsageNestedFixture(): array
{
    $key = StringHelper::randomString(8);
    $fields = Craft::$app->getFields();
    $entries = Craft::$app->getEntries();

    $formsField = new FormsField(['name' => 'Forms ' . $key, 'handle' => 'forms' . $key]);
    expect($fields->saveField($formsField))->toBeTrue();

    $block = new ContentBlock(['name' => 'Block ' . $key, 'handle' => 'block' . $key]);
    $block->setFieldLayout(formUsageNestedLayout(ContentBlockElement::class, [new CustomField($formsField)]));
    expect($fields->saveField($block))->toBeTrue();

    $nestedType = new EntryType(['name' => 'Nested ' . $key, 'handle' => 'nested' . $key, 'hasTitleField' => false, 'titleFormat' => '{id}']);
    $nestedType->setFieldLayout(formUsageNestedLayout(Entry::class, [new CustomField($formsField)]));
    expect($entries->saveEntryType($nestedType))->toBeTrue();

    $matrix = new Matrix(['name' => 'Matrix ' . $key, 'handle' => 'matrix' . $key]);
    $matrix->setEntryTypes([$nestedType]);
    expect($fields->saveField($matrix))->toBeTrue();

    $pageType = new EntryType(['name' => 'Page ' . $key, 'handle' => 'page' . $key, 'hasTitleField' => true]);
    $pageType->setFieldLayout(formUsageNestedLayout(Entry::class, [new EntryTitleField(), new CustomField($block), new CustomField($matrix)]));
    expect($entries->saveEntryType($pageType))->toBeTrue();

    $section = new Section(['name' => 'Section ' . $key, 'handle' => 'section' . $key, 'type' => Section::TYPE_CHANNEL]);
    $section->setEntryTypes([$pageType]);
    $section->setSiteSettings([new Section_SiteSettings(['siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'enabledByDefault' => true, 'hasUrls' => false])]);
    expect($entries->saveSection($section))->toBeTrue();

    return compact('formsField', 'block', 'nestedType', 'matrix', 'pageType', 'section') + ['entries' => []];
}

function formUsageNestedPage(array &$fixture, string $title, array $values): Entry
{
    $entry = new Entry([
        'sectionId' => $fixture['section']->id,
        'typeId' => $fixture['pageType']->id,
        'title' => $title,
    ]);
    $entry->setFieldValues($values);
    expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue();
    $fixture['entries'][] = $entry;

    return $entry;
}

/**
 * Returns the usage rows for a form as [element class, element ID, field handle, level].
 */
function formUsageNestedRows(int $formId): array
{
    $form = Formie::$plugin->getForms()->getFormById($formId);

    return array_map(fn(array $row): array => [
        $row['element']::class,
        (int)$row['element']->id,
        $row['field']?->handle,
        $row['level'],
    ], Formie::$plugin->getForms()->getFormUsage($form));
}

it('lists the owner entry of a Content Block that selects the form', function(): void {
    $fixture = formUsageNestedFixture();
    $this->formUsageNestedFixtures[] = &$fixture;
    $form = formie()->form()->singleLineTextField('name')->create();
    $entry = formUsageNestedPage($fixture, 'Block page', [
        $fixture['block']->handle => ['fields' => [$fixture['formsField']->handle => [$form->id]]],
    ]);
    $blockElement = $entry->getFieldValue($fixture['block']->handle);

    expect(formUsageNestedRows((int)$form->id))->toBe([
        [Entry::class, (int)$entry->id, $fixture['block']->handle, 0],
        [ContentBlockElement::class, (int)$blockElement->id, $fixture['formsField']->handle, 1],
    ]);
});

it('keeps listing the owner entry of a Matrix entry that selects the form', function(): void {
    $fixture = formUsageNestedFixture();
    $this->formUsageNestedFixtures[] = &$fixture;
    $form = formie()->form()->singleLineTextField('name')->create();
    $entry = formUsageNestedPage($fixture, 'Matrix page', [
        $fixture['matrix']->handle => [
            'new1' => ['type' => $fixture['nestedType']->handle, 'fields' => [$fixture['formsField']->handle => [$form->id]]],
        ],
    ]);
    $nested = $entry->getFieldValue($fixture['matrix']->handle)->status(null)->one();

    expect(formUsageNestedRows((int)$form->id))->toBe([
        [Entry::class, (int)$entry->id, $fixture['matrix']->handle, 0],
        [Entry::class, (int)$nested->id, $fixture['formsField']->handle, 1],
    ]);
});

it('loads nested usage elements and their owners in batches', function(): void {
    $fixture = formUsageNestedFixture();
    $this->formUsageNestedFixtures[] = &$fixture;
    $form = formie()->form()->singleLineTextField('name')->create();
    $handles = $fixture;

    $addPages = function(int $count) use (&$fixture, $form, $handles): void {
        for ($i = 0; $i < $count; $i++) {
            formUsageNestedPage($fixture, 'Matrix page ' . $i, [
                $handles['matrix']->handle => [
                    'new1' => ['type' => $handles['nestedType']->handle, 'fields' => [$handles['formsField']->handle => [$form->id]]],
                ],
            ]);
        }
    };

    // Counts the element queries one uncached usage lookup runs.
    $countQueries = function() use ($form): array {
        Formie::$plugin->getForms()->invalidateFormCaches();
        $queries = 0;
        $handler = static function() use (&$queries): void {
            $queries++;
        };
        Event::on(ElementQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, $handler);

        try {
            $rows = formUsageNestedRows((int)$form->id);
        } finally {
            Event::off(ElementQuery::class, ElementQuery::EVENT_BEFORE_PREPARE, $handler);
        }

        return [count($rows), $queries];
    };

    $addPages(2);
    [$fewRows, $fewQueries] = $countQueries();
    $addPages(6);
    [$manyRows, $manyQueries] = $countQueries();

    // Each page adds two rows. The related elements and their owners load in batches and each nested entry
    // is created with its owner, so more pages add no element queries.
    expect($fewRows)->toBe(4)
        ->and($manyRows)->toBe(16)
        ->and($manyQueries)->toBe($fewQueries);
});
