<?php

use craft\db\Connection;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\fields\Hidden;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260418_000000_field_definitions_and_form_fields;
use verbb\formie\migrations\m260828_000000_date_subfields_order;
use verbb\formie\migrations\m260927_010000_instance_configuration;

beforeEach(function(): void {
    $this->originalDb = Craft::$app->getDb();
    $this->recoveryDb = new Connection([
        'dsn' => $this->originalDb->dsn,
        'username' => $this->originalDb->username,
        'password' => $this->originalDb->password,
        'tablePrefix' => 'field_recovery_' . bin2hex(random_bytes(4)) . '_',
    ]);
    Craft::$app->set('db', $this->recoveryDb);
});

afterEach(function(): void {
    $db = $this->recoveryDb;

    try {
        $db->createCommand()->checkIntegrity(false)->execute();

        foreach ($db->getSchema()->getTableNames('', true) as $table) {
            if (str_starts_with($table, $db->tablePrefix)) {
                $db->createCommand()->dropTable($table)->execute();
            }
        }
    } finally {
        $db->createCommand()->checkIntegrity(true)->execute();
        Craft::$app->set('db', $this->originalDb);
        $db->close();
    }
});

function createRecoverableLegacyFields(Connection $db): void
{
    $migration = new class(['db' => $db]) extends Migration {};
    $migration->createTable(Table::FORMIE_FORM_FIELDS, [
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

    foreach ([1 => null, 2 => 1] as $id => $syncId) {
        $db->createCommand()->insert(Table::FORMIE_FORM_FIELDS, [
            'id' => $id,
            'layoutId' => $id,
            'pageId' => $id,
            'rowId' => $id,
            'syncId' => $syncId,
            'label' => 'Legacy hidden',
            'handle' => 'legacyHidden',
            'type' => Hidden::class,
            'sortOrder' => 0,
            'reference' => 'reference-' . $id,
            'settings' => Json::encode(['defaultOption' => 'dateInt', 'prePopulate' => 'campaign', 'required' => $id === 1]),
            'dateCreated' => '2024-01-01 00:00:00',
            'dateUpdated' => '2024-01-01 00:00:00',
            'uid' => sprintf('00000000-0000-4000-8000-%012d', $id),
        ])->execute();
    }

    $migration->createTable(Table::FORMIE_FORMS, ['id' => $migration->primaryKey(), 'settings' => $migration->text()]);
    $migration->createTable(Table::FORMIE_SUBMISSIONS, ['id' => $migration->primaryKey(), 'metadata' => $migration->json()]);
}

it('recovers renamed legacy fields before later migrations and retains synced identities', function(string $migrationClass): void {
    createRecoverableLegacyFields($this->recoveryDb);
    $migration = new $migrationClass();

    expect($migration->safeUp())->toBeTrue();

    $definition = (new Query())->from(Table::FORMIE_FIELDS)->one();
    $placements = (new Query())->from(Table::FORMIE_FORM_FIELDS)->orderBy('id')->all();

    expect((int)(new Query())->from(Table::FORMIE_FIELDS)->count())->toBe(1)
        ->and($definition['id'])->toBe(1)
        ->and($definition['type'])->toBe(Hidden::class)
        ->and($definition['handle'])->toBe('legacyHidden')
        ->and($definition['uid'])->toBe('00000000-0000-4000-8000-000000000001')
        ->and(array_column($placements, 'id'))->toBe([1, 2])
        ->and(array_column($placements, 'fieldId'))->toBe([1, 1])
        ->and(array_column($placements, 'reference'))->toBe(['reference-1', 'reference-2'])
        ->and(Json::decode($placements[0]['settings']))->toBe(['required' => true])
        ->and(Json::decode($placements[1]['settings']))->toBe(['required' => false]);

    $settings = Json::decode($definition['settings']);

    if ($migrationClass === m260927_010000_instance_configuration::class) {
        expect($settings['valueSource'])->toBe('dateInt')
            ->and($settings['prefillQueryParam'])->toBe('campaign');
    } else {
        expect($settings['defaultOption'])->toBe('dateInt')
            ->and($settings['prePopulate'])->toBe('campaign');
    }

    expect($migration->safeUp())->toBeTrue()
        ->and((new Query())->from(Table::FORMIE_FIELDS)->one())->toBe($definition)
        ->and((new Query())->from(Table::FORMIE_FORM_FIELDS)->orderBy('id')->all())->toBe($placements);
})->with([
    m260418_000000_field_definitions_and_form_fields::class,
    m260828_000000_date_subfields_order::class,
    m260927_010000_instance_configuration::class,
]);

it('stops before writing when missing definitions cannot be recovered', function(string $migrationClass, bool $stripped): void {
    createRecoverableLegacyFields($this->recoveryDb);
    $db = $this->recoveryDb;
    $db->createCommand()->addColumn(Table::FORMIE_FORM_FIELDS, 'fieldId', 'integer')->execute();
    $db->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['fieldId' => 1, 'settings' => '{"required":true}'], ['id' => 1])->execute();

    if ($stripped) {
        foreach (['syncId', 'label', 'handle', 'type'] as $column) {
            $db->createCommand()->dropColumn(Table::FORMIE_FORM_FIELDS, $column)->execute();
        }
    }

    $before = (new Query())->from(Table::FORMIE_FORM_FIELDS)->orderBy('id')->all();
    $migration = new $migrationClass();

    expect(fn() => $migration->safeUp())->toThrow(RuntimeException::class, 'Restore a complete pre-update database backup')
        ->and($db->tableExists(Table::FORMIE_FIELDS, true))->toBeFalse()
        ->and($db->tableExists('{{%formie_instance_configs}}', true))->toBeFalse()
        ->and((new Query())->from(Table::FORMIE_FORM_FIELDS)->orderBy('id')->all())->toBe($before);
})->with([
    [m260418_000000_field_definitions_and_form_fields::class, false],
    [m260418_000000_field_definitions_and_form_fields::class, true],
    [m260828_000000_date_subfields_order::class, false],
    [m260828_000000_date_subfields_order::class, true],
    [m260927_010000_instance_configuration::class, false],
    [m260927_010000_instance_configuration::class, true],
]);
