<?php

use craft\db\Connection;
use craft\db\Query;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261014_000000_payment_field_foreign_keys;

it('repairs legacy payment field constraints without losing financial history', function (bool $bothConstraints): void {
    $source = Craft::$app->getDb();
    $db = new Connection([
        'dsn' => $source->dsn,
        'username' => $source->username,
        'password' => $source->password,
        'tablePrefix' => 'payment_fk_' . bin2hex(random_bytes(4)) . '_',
    ]);
    $migration = new m261014_000000_payment_field_foreign_keys(['db' => $db]);
    $tables = [Table::FORMIE_FIELDS, Table::FORMIE_FORM_FIELDS, Table::FORMIE_PAYMENTS, Table::FORMIE_SUBSCRIPTIONS];

    try {
        foreach (array_slice($tables, 0, 2) as $table) {
            $db->createCommand()->createTable($table, ['id' => 'int PRIMARY KEY'])->execute();
        }
        $db->createCommand()->batchInsert(Table::FORMIE_FIELDS, ['id'], [[1], [2]])->execute();
        $db->createCommand()->batchInsert(Table::FORMIE_FORM_FIELDS, ['id'], [[1], [3]])->execute();
        if ($bothConstraints) {
            $db->createCommand()->insert(Table::FORMIE_FORM_FIELDS, ['id' => 2])->execute();
        }

        foreach (array_slice($tables, 2) as $index => $table) {
            $db->createCommand()->createTable($table, ['id' => 'int PRIMARY KEY', 'fieldId' => 'int NULL', 'scope' => 'text'])->execute();
            $db->createCommand()->addForeignKey('legacy_field_' . $db->tablePrefix . $index, $table, 'fieldId', Table::FORMIE_FIELDS, 'id', 'SET NULL')->execute();
            $db->createCommand()->batchInsert($table, ['id', 'fieldId', 'scope'], [[1, 1, '{"amount":"25.00"}'], [2, 2, '{"amount":"10.00"}']])->execute();
            if ($bothConstraints) {
                $db->createCommand()->addForeignKey('instance_field_' . $db->tablePrefix . $index, $table, 'fieldId', Table::FORMIE_FORM_FIELDS, 'id', 'SET NULL')->execute();
            }
        }

        expect($migration->safeUp())->toBeTrue()->and($migration->safeUp())->toBeTrue();

        foreach (array_slice($tables, 2) as $table) {
            expect((new Query())->from($table)->where(['id' => 1])->one($db)['fieldId'])->toBe(1);
            $orphan = (new Query())->from($table)->where(['id' => 2])->one($db);
            expect($orphan['fieldId'])->toBe($bothConstraints ? 2 : null)
                ->and(json_decode($orphan['scope'], true))->toBe($bothConstraints ? ['amount' => '10.00'] : ['amount' => '10.00', 'legacyFieldId' => 2]);
            $db->createCommand()->insert($table, ['id' => 3, 'fieldId' => 3, 'scope' => '{"amount":"30.00"}'])->execute();
        }

        $db->createCommand()->delete(Table::FORMIE_FORM_FIELDS, ['id' => 3])->execute();
        foreach (array_slice($tables, 2) as $table) {
            $row = (new Query())->from($table)->where(['id' => 3])->one($db);
            expect($row['fieldId'])->toBeNull()->and($row['scope'])->toBe('{"amount":"30.00"}');
        }
    } finally {
        foreach (array_reverse($tables) as $table) {
            $db->createCommand()->dropTable($table)->execute();
        }
        $db->close();
    }
})->with(['legacy constraint' => false, 'both legacy and instance constraints' => true]);
