<?php

declare(strict_types=1);

use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\fields\Categories;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_070000_categories_sources;

const CATEGORIES_SOURCES_TRACK = 'plugin:formie';

// Dates the applied history as an upgrade: everything before the Craft 5 conversion long ago, and
// everything from the conversion onwards at the times given.
function categoriesSourcesHistory(string $installed, string $conversion, string $current): void
{
    $db = Craft::$app->getDb();
    $migrations = (new Query())->select(['id', 'name'])->from(CraftTable::MIGRATIONS)->where(['track' => CATEGORIES_SOURCES_TRACK])->all();

    foreach ($migrations as $migration) {
        $applied = match (true) {
            $migration['name'] < 'm231125_000000_craft5' => $installed,
            $migration['name'] < 'm251115_000000' => $conversion,
            default => $current,
        };

        $db->createCommand()->update(CraftTable::MIGRATIONS, ['applyTime' => $applied], ['id' => $migration['id']], [], false)->execute();
    }

    $db->createCommand()->update(CraftTable::PLUGINS, ['installDate' => $installed], ['handle' => 'formie'], [], false)->execute();
}

function categoriesSourcesField(Form $form, string $handle, array $settings, string $created, ?string $updated = null): int
{
    $id = $form->getFieldByHandle($handle)->definitionId;
    $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $id])->scalar());

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, [
        'settings' => Json::encode(array_merge($stored, $settings)),
        'dateCreated' => $created,
        'dateUpdated' => $updated ?? $created,
    ], ['id' => $id], [], false)->execute();

    return (int)$id;
}

function categoriesSourcesRow(int $id): array
{
    $row = (new Query())->from(Table::FORMIE_FIELDS)->where(['id' => $id])->one();
    $row['settings'] = Json::decode($row['settings']);

    return $row;
}

function categoriesSourcesRun(): void
{
    expect((new m261104_070000_categories_sources())->safeUp())->toBeTrue();
}

beforeEach(function(): void {
    $this->transaction = Craft::$app->getDb()->beginTransaction();
    $this->source = 'group:' . Craft::$app->getCategories()->getGroupByHandle('formieTestCategories')->uid;
    $this->form = formie()->form()
        ->categoriesField('allSources')
        ->categoriesField('severalSources')
        ->categoriesField('newField')
        ->categoriesField('editedField')
        ->categoriesField('noSource')
        ->create();
});

afterEach(function(): void {
    $this->transaction->rollBack();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

it('restores source lists on a site upgraded straight from Formie 2, and only those', function(): void {
    $installed = '2021-01-01 00:00:00';
    $upgrade = '2026-11-03 12:40:24';
    categoriesSourcesHistory($installed, $upgrade, '2026-11-03 12:51:07');

    $single = ['allowMultipleSources' => false, 'sources' => '*', 'source' => $this->source];
    $all = categoriesSourcesField($this->form, 'allSources', $single, '2026-11-03 12:28:25', '2026-11-03 12:45:34');
    $several = categoriesSourcesField($this->form, 'severalSources', ['sources' => [$this->source], 'allowMultipleSources' => false, 'source' => $this->source], '2026-11-03 12:28:48');
    $new = categoriesSourcesField($this->form, 'newField', $single, '2026-11-04 09:00:00');
    $edited = categoriesSourcesField($this->form, 'editedField', $single, '2026-11-03 12:28:25', '2026-11-04 09:00:00');
    $noSource = categoriesSourcesField($this->form, 'noSource', ['allowMultipleSources' => false, 'sources' => '*', 'source' => null], '2026-11-03 12:28:25');

    $before = array_map('categoriesSourcesRow', [$all, $several, $new, $edited, $noSource]);

    categoriesSourcesRun();

    [$allRow, $severalRow, $newRow, $editedRow, $noSourceRow] = array_map('categoriesSourcesRow', [$all, $several, $new, $edited, $noSource]);

    expect($allRow['settings']['allowMultipleSources'])->toBeTrue()
        ->and($allRow['settings']['sources'])->toBe('*')
        ->and($allRow['settings']['source'])->toBe($this->source)
        ->and($allRow['dateUpdated'])->toBe($before[0]['dateUpdated'])
        ->and($severalRow['settings']['allowMultipleSources'])->toBeTrue()
        ->and($severalRow['settings']['sources'])->toBe([$this->source])
        ->and($newRow)->toBe($before[2])
        ->and($editedRow)->toBe($before[3])
        ->and($noSourceRow)->toBe($before[4]);

    $reloaded = Form::find()->id($this->form->id)->one();
    expect($reloaded->getFieldByHandle('allSources')->getInputSources())->toBe('*')
        ->and($reloaded->getFieldByHandle('newField')->getInputSources())->toBe([$this->source]);

    // A second run must find nothing left to change.
    $after = array_map('categoriesSourcesRow', [$all, $several, $new, $edited, $noSource]);
    categoriesSourcesRun();

    expect(array_map('categoriesSourcesRow', [$all, $several, $new, $edited, $noSource]))->toBe($after);
});

it('leaves single-source fields alone on other install histories', function(string $installed, string $conversion, string $current, string $created): void {
    categoriesSourcesHistory($installed, $conversion, $current);

    $id = categoriesSourcesField($this->form, 'allSources', ['allowMultipleSources' => false, 'sources' => '*', 'source' => $this->source], $created);
    $before = categoriesSourcesRow($id);

    categoriesSourcesRun();

    expect(categoriesSourcesRow($id))->toBe($before);
})->with([
    'upgraded through Formie 3, which already offered the one source' => ['2021-01-01 00:00:00', '2024-09-01 00:00:00', '2026-11-03 12:51:07', '2024-08-31 23:00:00'],
    'Formie 3 installed fresh, then upgraded the same day' => ['2026-11-03 12:00:00', '2026-11-03 12:00:05', '2026-11-03 13:00:00', '2026-11-03 12:30:00'],
    'Formie 4 installed fresh' => ['2026-11-03 12:00:00', '2026-11-03 12:00:05', '2026-11-03 12:00:06', '2026-11-03 12:00:01'],
]);

it('leaves fields alone when the Craft 5 conversion has not been applied', function(): void {
    Craft::$app->getDb()->createCommand()->delete(CraftTable::MIGRATIONS, ['track' => CATEGORIES_SOURCES_TRACK, 'name' => 'm231125_000000_craft5'])->execute();

    $id = categoriesSourcesField($this->form, 'allSources', ['allowMultipleSources' => false, 'sources' => '*', 'source' => $this->source], '2020-01-01 00:00:00');
    $before = categoriesSourcesRow($id);

    categoriesSourcesRun();

    expect(categoriesSourcesRow($id))->toBe($before);
});

it('only touches Categories fields', function(): void {
    categoriesSourcesHistory('2021-01-01 00:00:00', '2026-11-03 12:40:24', '2026-11-03 12:51:07');

    $tagSource = 'taggroup:' . Craft::$app->getTags()->getTagGroupByHandle('formieTestTags')->uid;
    $form = formie()->form()->tagsField('labels', ['source' => $tagSource])->create();
    $id = (int)$form->getFieldByHandle('labels')->definitionId;

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['dateCreated' => '2026-11-03 12:28:25', 'dateUpdated' => '2026-11-03 12:28:25'], ['id' => $id], [], false)->execute();
    $before = categoriesSourcesRow($id);

    categoriesSourcesRun();

    expect(categoriesSourcesRow($id))->toBe($before)
        ->and($before['settings']['allowMultipleSources'])->toBeFalse();
});

it('reads a restored field as all sources through the real class', function(): void {
    $field = new Categories(['allowMultipleSources' => true, 'sources' => '*', 'source' => $this->source]);

    expect($field->getInputSources())->toBe('*');
});
