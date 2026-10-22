<?php

use craft\db\Connection;
use craft\db\Query;
use craft\helpers\StringHelper;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260418_000000_field_definitions_and_form_fields;
use verbb\formie\migrations\m261023_000000_field_definition_sequence;

beforeEach(function(): void {
    $this->originalDb = Craft::$app->getDb();
    $this->sequenceDb = new Connection([
        // An explicit disposable PostgreSQL connection exercises serial sequences locally.
        'dsn' => getenv('TEST_MIGRATION_DSN') ?: $this->originalDb->dsn,
        'username' => getenv('TEST_MIGRATION_USER') ?: $this->originalDb->username,
        'password' => getenv('TEST_MIGRATION_PASSWORD') ?: $this->originalDb->password,
        'tablePrefix' => 'seq_' . bin2hex(random_bytes(4)) . '_',
    ]);
    Craft::$app->set('db', $this->sequenceDb);
});

afterEach(function(): void {
    $db = $this->sequenceDb;

    try {
        foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS, Table::FORMIE_FORM_FIELDS, Table::FORMIE_FIELDS] as $table) {
            if ($db->tableExists($table, true)) {
                $db->createCommand()->dropTable($table)->execute();
            }
        }
    } finally {
        Craft::$app->set('db', $this->originalDb);
        $db->close();
    }
});

function sequenceFieldAttributes(): array
{
    return [
        'type' => SingleLineText::class,
        'label' => 'Legacy text',
        'handle' => 'legacyText',
        'settings' => '{"required":true,"defaultValue":"Preserved"}',
        'dateCreated' => '2024-01-01 00:00:00',
        'dateUpdated' => '2024-01-01 00:00:00',
        'uid' => StringHelper::UUID(),
    ];
}

it('allocates new field IDs after a populated legacy split or interrupted rename', function(bool $renamed): void {
    $db = $this->sequenceDb;
    $migration = new m260418_000000_field_definitions_and_form_fields(['db' => $db]);
    $legacyTable = $renamed ? Table::FORMIE_FORM_FIELDS : Table::FORMIE_FIELDS;
    $migration->createTable($legacyTable, [
        'id' => $migration->primaryKey(),
        'layoutId' => $migration->integer()->notNull(),
        'pageId' => $migration->integer()->notNull(),
        'rowId' => $migration->integer()->notNull(),
        'syncId' => $migration->integer(),
        'label' => $migration->text()->notNull(),
        'handle' => $migration->string()->notNull(),
        'type' => $migration->string()->notNull(),
        'sortOrder' => $migration->integer(),
        'reference' => $migration->string(),
        'settings' => $migration->text(),
        'dateCreated' => $migration->dateTime()->notNull(),
        'dateUpdated' => $migration->dateTime()->notNull(),
        'uid' => $migration->uid(),
    ]);

    foreach ([1 => null, 23 => null, 441 => null, 442 => 23] as $id => $syncId) {
        $db->createCommand()->insert($legacyTable, array_merge(sequenceFieldAttributes(), [
            'id' => $id, 'layoutId' => 1, 'pageId' => 1, 'rowId' => 1,
            'syncId' => $syncId, 'reference' => 'reference-' . $id,
        ]))->execute();
    }

    $db->createCommand()->resetSequence($legacyTable, 700)->execute();

    foreach ([Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS] as $table) {
        $migration->createTable($table, ['id' => $migration->primaryKey(), 'fieldId' => $migration->integer()]);
    }

    expect($migration->up())->toBeTrue();
    $definitions = (new Query())->from(Table::FORMIE_FIELDS)->orderBy('id')->all();
    $placements = (new Query())->from(Table::FORMIE_FORM_FIELDS)->orderBy('id')->all();

    expect(array_column($definitions, 'id'))->toBe([1, 23, 441])
        ->and(array_column($placements, 'fieldId'))->toBe([1, 23, 441, 23]);

    // This insert fails with a duplicate primary key when PostgreSQL's new sequence stays at 1.
    $db->createCommand()->insert(Table::FORMIE_FIELDS, sequenceFieldAttributes())->execute();
    expect((int)$db->getLastInsertID())->toBe(442)
        ->and((new Query())->from(Table::FORMIE_FIELDS)->where(['id' => [1, 23, 441]])->orderBy('id')->all())->toBe($definitions);

    $db->createCommand()->insert(Table::FORMIE_FORM_FIELDS, [
        'fieldId' => 442, 'layoutId' => 1, 'pageId' => 1, 'rowId' => 1,
        'dateCreated' => '2024-01-01 00:00:00', 'dateUpdated' => '2024-01-01 00:00:00',
        'uid' => StringHelper::UUID(), 'reference' => 'new-placement',
    ])->execute();
    expect((int)$db->getLastInsertID())->toBe(700)
        ->and((new Query())->from(Table::FORMIE_FORM_FIELDS)->where(['id' => [1, 23, 441, 442]])->orderBy('id')->all())->toBe($placements);
})->with(['normal upgrade' => false, 'interrupted rename' => true]);

it('repairs already upgraded field sequences without changing definitions and can run again', function(array $ids): void {
    $db = $this->sequenceDb;
    $migration = new m261023_000000_field_definition_sequence(['db' => $db]);
    $migration->createTable(Table::FORMIE_FIELDS, [
        'id' => $migration->primaryKey(),
        'type' => $migration->string()->notNull(),
        'label' => $migration->text()->notNull(),
        'handle' => $migration->string(64)->notNull(),
        'settings' => $migration->text(),
        'dateCreated' => $migration->dateTime()->notNull(),
        'dateUpdated' => $migration->dateTime()->notNull(),
        'uid' => $migration->uid(),
    ]);

    foreach ($ids as $id) {
        $db->createCommand()->insert(Table::FORMIE_FIELDS, ['id' => $id] + sequenceFieldAttributes())->execute();
    }

    // Explicit inserts leave PostgreSQL's sequence untouched, as in the original upgrade.
    $before = (new Query())->from(Table::FORMIE_FIELDS)->orderBy('id')->all();
    expect($migration->up())->toBeTrue()
        ->and((new Query())->from(Table::FORMIE_FIELDS)->orderBy('id')->all())->toBe($before);

    $nextId = $ids ? max($ids) + 1 : 1;
    $db->createCommand()->insert(Table::FORMIE_FIELDS, sequenceFieldAttributes())->execute();
    expect((int)$db->getLastInsertID())->toBe($nextId);

    expect($migration->up())->toBeTrue();
    $db->createCommand()->insert(Table::FORMIE_FIELDS, sequenceFieldAttributes())->execute();
    expect((int)$db->getLastInsertID())->toBe($nextId + 1);
})->with(['empty installation' => [[]], 'populated upgrade' => [[1, 23, 441]]]);
