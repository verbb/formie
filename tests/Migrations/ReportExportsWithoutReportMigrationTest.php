<?php

use craft\db\Connection;
use craft\db\Query;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261103_000000_report_exports_without_report;

beforeEach(function(): void {
    $this->originalDb = Craft::$app->getDb();
    $this->migrationDb = new Connection([
        // An explicit disposable PostgreSQL connection checks its separate NOT NULL handling.
        'dsn' => getenv('TEST_MIGRATION_DSN') ?: $this->originalDb->dsn,
        'username' => getenv('TEST_MIGRATION_USER') ?: $this->originalDb->username,
        'password' => getenv('TEST_MIGRATION_PASSWORD') ?: $this->originalDb->password,
        'tablePrefix' => 'rex_' . bin2hex(random_bytes(4)) . '_',
    ]);
    Craft::$app->set('db', $this->migrationDb);
});

afterEach(function(): void {
    $db = $this->migrationDb;

    try {
        foreach ([Table::FORMIE_REPORT_EXPORTS, Table::FORMIE_REPORTS] as $table) {
            if ($db->tableExists($table, true)) {
                $db->createCommand()->dropTable($table)->execute();
            }
        }
    } finally {
        Craft::$app->set('db', $this->originalDb);
        $db->close();
    }
});

it('lets exports exist without a report while keeping existing rows and the foreign key', function(): void {
    $db = $this->migrationDb;
    $db->createCommand()->createTable(Table::FORMIE_REPORTS, ['id' => 'pk', 'name' => 'string'])->execute();
    $db->createCommand()->createTable(Table::FORMIE_REPORT_EXPORTS, ['id' => 'pk', 'reportId' => 'integer NOT NULL', 'format' => 'string'])->execute();
    $db->createCommand()->addForeignKey('rex_fk', Table::FORMIE_REPORT_EXPORTS, 'reportId', Table::FORMIE_REPORTS, 'id', 'CASCADE')->execute();
    $db->createCommand()->insert(Table::FORMIE_REPORTS, ['name' => 'Saved'])->execute();
    $reportId = (int)$db->getLastInsertID();
    $db->createCommand()->insert(Table::FORMIE_REPORT_EXPORTS, ['reportId' => $reportId, 'format' => 'csv'])->execute();

    expect($db->getTableSchema(Table::FORMIE_REPORT_EXPORTS, true)->getColumn('reportId')->allowNull)->toBeFalse();

    $migration = new m261103_000000_report_exports_without_report(['db' => $db, 'compact' => true]);
    expect($migration->safeUp())->toBeTrue()
        ->and($db->getTableSchema(Table::FORMIE_REPORT_EXPORTS, true)->getColumn('reportId')->allowNull)->toBeTrue();

    // A repeat run is harmless, and a new export can be saved with no report.
    expect($migration->safeUp())->toBeTrue();
    $db->createCommand()->insert(Table::FORMIE_REPORT_EXPORTS, ['reportId' => null, 'format' => 'xlsx'])->execute();

    $rows = (new Query())->from(Table::FORMIE_REPORT_EXPORTS)->orderBy(['id' => SORT_ASC])->all($db);
    expect((int)$rows[0]['reportId'])->toBe($reportId)
        ->and($rows[1]['reportId'])->toBeNull();

    // Deleting the saved report still removes its exports, and leaves the others.
    $db->createCommand()->delete(Table::FORMIE_REPORTS, ['id' => $reportId])->execute();
    expect((new Query())->from(Table::FORMIE_REPORT_EXPORTS)->select('format')->column($db))->toBe(['xlsx']);
});
