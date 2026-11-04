<?php

declare(strict_types=1);

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m231125_000000_craft5;

const LEGACY_NESTED_ROWS_TABLE = '{{%formie_nestedfieldrows}}';

function legacyNestedMigrationInvoke(string $method, array $arguments = []): mixed
{
    $migration = new m231125_000000_craft5();

    ob_start();

    try {
        return (new ReflectionMethod($migration, $method))->invokeArgs($migration, $arguments);
    } finally {
        ob_end_clean();
    }
}

/**
 * Builds the Formie 2 records a nested field left behind, next to the layout an upgrade has already created for it.
 *
 * @return array{parentUid: string, table: string}
 */
function legacyNestedRecords(Form $form, string $handle, string $type, array $childContexts, array $columns): array
{
    $db = Craft::$app->getDb();
    $now = Db::prepareDateForDb(new DateTime());
    $parentUid = StringHelper::UUID();
    $table = '{{%fmc_legacy_' . strtolower(StringHelper::randomString(8)) . '}}';
    $migration = new class() extends Migration {};

    ob_start();
    $migration->createTable($table, [
        'id' => $migration->primaryKey(),
        'elementId' => $migration->integer()->notNull(),
        'siteId' => $migration->integer()->notNull(),
        ...array_map(fn() => $migration->string(), array_flip($columns)),
    ]);
    ob_end_clean();

    $GLOBALS['legacyNestedFixture']['tables'][] = $table;

    $insertField = function(string $fieldHandle, string $fieldType, string $context, array $settings, string $uid, ?string $suffix = null) use ($db, $now): void {
        $db->createCommand()->insert('{{%fields}}', [
            'name' => $fieldHandle,
            'handle' => $fieldHandle,
            'context' => $context,
            'columnSuffix' => $suffix,
            'type' => $fieldType,
            'settings' => Json::encode($settings),
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => $uid,
        ])->execute();
        $GLOBALS['legacyNestedFixture']['fields'][] = (int)$db->getLastInsertID();
    };

    $insertField($handle, 'verbb\\formie\\fields\\formfields\\' . $type, 'formie:' . (new Query())->select('uid')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar(), ['contentTable' => $table], $parentUid);

    foreach ($childContexts as $childHandle => $context) {
        $insertField($childHandle, 'verbb\\formie\\fields\\formfields\\SingleLineText', str_replace('{parent}', $parentUid, $context), [], StringHelper::UUID());
    }

    return ['parentUid' => $parentUid, 'table' => $table];
}

function legacyNestedRow(int $ownerId, string $parentUid, string $table, array $values, int $sortOrder = 1): void
{
    $db = Craft::$app->getDb();
    $parentId = (new Query())->select('id')->from('{{%fields}}')->where(['uid' => $parentUid])->scalar();
    $elementId = random_int(1000000, 2000000);

    $db->createCommand()->insert(LEGACY_NESTED_ROWS_TABLE, ['id' => $elementId, 'ownerId' => $ownerId, 'fieldId' => $parentId, 'sortOrder' => $sortOrder])->execute();
    $db->createCommand()->insert($table, ['elementId' => $elementId, 'siteId' => Craft::$app->getSites()->getPrimarySite()->id, ...$values])->execute();
}

function legacyStoredContent(int $submissionId): array
{
    return Json::decode((new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submissionId])->scalar()) ?: [];
}

function legacySetStoredContent(int $submissionId, array $content): void
{
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submissionId]);
}

function legacyTextChild(string $handle): array
{
    return ['type' => SingleLineText::class, 'handle' => $handle, 'label' => ucfirst($handle)];
}

beforeEach(function(): void {
    $GLOBALS['legacyNestedFixture'] = ['tables' => [], 'fields' => []];
    $migration = new class() extends Migration {};

    ob_start();
    $migration->createTable(LEGACY_NESTED_ROWS_TABLE, [
        'id' => $migration->integer()->notNull(),
        'ownerId' => $migration->integer()->notNull(),
        'fieldId' => $migration->integer()->notNull(),
        'sortOrder' => $migration->integer(),
    ]);
    ob_end_clean();
});

afterEach(function(): void {
    $migration = new class() extends Migration {};

    ob_start();

    foreach ([LEGACY_NESTED_ROWS_TABLE, ...$GLOBALS['legacyNestedFixture']['tables']] as $table) {
        $migration->dropTableIfExists($table);
    }

    ob_end_clean();

    Craft::$app->getDb()->createCommand()->delete('{{%fields}}', ['id' => $GLOBALS['legacyNestedFixture']['fields']])->execute();
});

it('keeps group answers when the child fields hold a stale parent context', function(array $contexts, array $columns): void {
    $form = formie()->form()->groupField('details', ['rows' => [['fields' => [legacyTextChild('first'), legacyTextChild('second')]]]])->create();
    $submission = formie()->submission($form)->save();
    $empty = formie()->submission($form)->save();
    legacySetStoredContent($submission->id, []);
    legacySetStoredContent($empty->id, []);
    $records = legacyNestedRecords($form, 'details', 'Group', $contexts, $columns);
    legacyNestedRow($submission->id, $records['parentUid'], $records['table'], [$columns[0] => 'First 東京', $columns[1] => '']);

    legacyNestedMigrationInvoke('_migrateNestedContent');

    $group = Form::find()->id($form->id)->one()->getFieldByHandle('details');
    $firstUid = $group->getFieldByHandle('first')->uid;
    $secondUid = $group->getFieldByHandle('second')->uid;

    expect(legacyStoredContent($submission->id))->toEqual([$group->uid => [$firstUid => 'First 東京', $secondUid => '']])
        ->and(legacyStoredContent($empty->id))->toBe([]);

    $reloaded = Submission::find()->id($submission->id)->status(null)->one();

    expect($reloaded->getFieldValue('details.first'))->toBe('First 東京')
        ->and($reloaded->getFieldValue('details.second'))->toBe('')
        ->and($group->getValueAsString($reloaded->getFieldValue('details'), $reloaded))->toBe('First 東京');

    // A second pass finds the answers already in place.
    legacyNestedMigrationInvoke('_migrateNestedContent');

    expect(legacyStoredContent($submission->id))->toEqual([$group->uid => [$firstUid => 'First 東京', $secondUid => '']]);
})->with([
    'stale context without column suffixes' => [['first' => 'formieField:' . '11111111-2222-4333-8444-555555555555', 'second' => 'formieField:' . '11111111-2222-4333-8444-555555555555'], ['field_first', 'field_second']],
    'stale context with column suffixes' => [['first' => 'formieField:' . '11111111-2222-4333-8444-555555555555', 'second' => 'formieField:' . '11111111-2222-4333-8444-555555555555'], ['field_first_abcdefgh', 'field_second_hgfedcba']],
    'matching context' => [['first' => 'formieField:{parent}', 'second' => 'formieField:{parent}'], ['field_first_abcdefgh', 'field_second_hgfedcba']],
]);

it('keeps repeater rows in order when the child fields hold a stale parent context', function(): void {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [legacyTextChild('name')]]]])->create();
    $submission = formie()->submission($form)->save();
    legacySetStoredContent($submission->id, []);
    $records = legacyNestedRecords($form, 'people', 'Repeater', ['name' => 'formieField:11111111-2222-4333-8444-555555555555'], ['field_name']);
    legacyNestedRow($submission->id, $records['parentUid'], $records['table'], ['field_name' => 'Ada'], 1);
    legacyNestedRow($submission->id, $records['parentUid'], $records['table'], ['field_name' => 'Grace'], 2);

    legacyNestedMigrationInvoke('_migrateNestedContent');

    $repeater = Form::find()->id($form->id)->one()->getFieldByHandle('people');
    $nameUid = $repeater->getFieldByHandle('name')->uid;

    expect($repeater)->toBeInstanceOf(Repeater::class)
        ->and(legacyStoredContent($submission->id))->toBe([$repeater->uid => [[$nameUid => 'Ada'], [$nameUid => 'Grace']]]);
});

it('never attaches a stale group row to another form or a column outside the group layout', function(): void {
    $form = formie()->form()->groupField('details', ['rows' => [['fields' => [legacyTextChild('first')]]]])->create();
    $otherForm = formie()->form()->groupField('details', ['rows' => [['fields' => [legacyTextChild('first')]]]])->create();
    $own = formie()->submission($form)->save();
    $foreign = formie()->submission($otherForm)->save();
    legacySetStoredContent($own->id, []);
    legacySetStoredContent($foreign->id, ['untouched' => 'value']);
    $records = legacyNestedRecords($form, 'details', 'Group', [
        'first' => 'formieField:11111111-2222-4333-8444-555555555555',
        'removed' => 'formieField:11111111-2222-4333-8444-555555555555',
    ], ['field_first', 'field_removed']);
    legacyNestedRow($own->id, $records['parentUid'], $records['table'], ['field_first' => 'Mine', 'field_removed' => 'Not in the layout']);
    legacyNestedRow($foreign->id, $records['parentUid'], $records['table'], ['field_first' => 'Belongs elsewhere']);

    legacyNestedMigrationInvoke('_migrateNestedContent');

    $group = Form::find()->id($form->id)->one()->getFieldByHandle('details');

    expect(legacyStoredContent($own->id))->toBe([$group->uid => [$group->getFieldByHandle('first')->uid => 'Mine']])
        ->and(legacyStoredContent($foreign->id))->toBe(['untouched' => 'value']);
});

it('keeps top-level answers from a form table when no field holds the form context', function(): void {
    $form = formie()->form()->singleLineTextField('headline')->create();
    $submission = formie()->submission($form)->save();
    $records = legacyNestedRecords($form, 'container', 'Group', [], ['field_headline_abcdefgh', 'field_other']);
    Craft::$app->getDb()->createCommand()->insert($records['table'], ['elementId' => $submission->id, 'siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'field_headline_abcdefgh' => 'Kept', 'field_other' => 'Dropped'])->execute();
    $layoutId = (new Query())->select('layoutId')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();
    $layout = Formie::$plugin->getFields()->getLayoutById((int)$layoutId);

    $content = legacyNestedMigrationInvoke('_createContentJson', [$records['table'], $submission->id, 'formie:' . StringHelper::UUID(), $layout]);

    expect($content)->toBe([Form::find()->id($form->id)->one()->getFieldByHandle('headline')->uid => 'Kept']);
});
