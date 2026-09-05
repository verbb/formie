<?php

declare(strict_types=1);

use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\db\Query;
use verbb\formie\fields\Date;
use verbb\formie\fields\subfields\DateYearDropdown;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260828_000000_date_subfields_order;

it('reorders default date subfields and repairs year range on formie_form_fields layouts', function (): void {
    $db = Craft::$app->getDb();

    if (!$db->tableExists(Table::FORMIE_FORM_FIELDS) || !$db->columnExists(Table::FORMIE_FORM_FIELDS, 'layoutId')) {
        skip('Requires Formie 4 form_fields placement schema.');
    }

    $now = Db::prepareDateForDb(new DateTime());

    $db->createCommand()->insert(Table::FORMIE_FIELD_LAYOUTS, [
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ])->execute();
    $layoutId = (int)$db->getLastInsertID();

    $db->createCommand()->insert(Table::FORMIE_FIELD_LAYOUT_PAGES, [
        'layoutId' => $layoutId,
        'label' => 'Page',
        'sortOrder' => 0,
        'settings' => Json::encode([]),
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ])->execute();
    $pageId = (int)$db->getLastInsertID();

    $db->createCommand()->insert(Table::FORMIE_FIELD_LAYOUT_ROWS, [
        'layoutId' => $layoutId,
        'pageId' => $pageId,
        'sortOrder' => 0,
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ])->execute();
    $rowId = (int)$db->getLastInsertID();

    $yearFieldId = null;

    foreach (m260828_000000_date_subfields_order::MIGRATED_ORDER as $index => $handle) {
        $settings = $handle === 'year'
            ? ['minYearRange' => 100, 'maxYearRange' => 100]
            : [];

        $db->createCommand()->insert(Table::FORMIE_FIELDS, [
            'type' => $handle === 'year' ? DateYearDropdown::class : Date::class,
            'label' => ucfirst($handle),
            'handle' => $handle,
            'settings' => Json::encode($settings),
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => StringHelper::UUID(),
        ])->execute();
        $fieldId = (int)$db->getLastInsertID();

        if ($handle === 'year') {
            $yearFieldId = $fieldId;
        }

        $db->createCommand()->insert(Table::FORMIE_FORM_FIELDS, [
            'fieldId' => $fieldId,
            'layoutId' => $layoutId,
            'pageId' => $pageId,
            'rowId' => $rowId,
            'sortOrder' => $index,
            'settings' => null,
            'reference' => StringHelper::UUID(),
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => StringHelper::UUID(),
        ])->execute();
    }

    $db->createCommand()->insert(Table::FORMIE_FIELDS, [
        'type' => Date::class,
        'label' => 'Event Date',
        'handle' => 'eventDate' . uniqid(),
        'settings' => Json::encode([
            'displayType' => 'dropdowns',
            'includeDate' => true,
            'includeTime' => true,
            'dateFormat' => 'd/m/Y',
            'timeFormat' => 'H:i',
            'nestedLayoutId' => $layoutId,
            'minYearRange' => -50,
            'maxYearRange' => 25,
        ]),
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ])->execute();

    $migration = new m260828_000000_date_subfields_order();
    expect($migration->safeUp())->toBeTrue();

    $orderedHandles = (new Query())
        ->select(['f.handle'])
        ->from(['ff' => Table::FORMIE_FORM_FIELDS])
        ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
        ->where(['ff.layoutId' => $layoutId])
        ->orderBy(['ff.sortOrder' => SORT_ASC])
        ->column();

    expect($orderedHandles)->toBe(['day', 'month', 'year', 'hour', 'minute', 'second', 'ampm']);

    $yearSettings = Json::decode((new Query())
        ->select(['settings'])
        ->from([Table::FORMIE_FIELDS])
        ->where(['id' => $yearFieldId])
        ->scalar()) ?? [];

    expect((int)$yearSettings['minYearRange'])->toBe(-50)
        ->and((int)$yearSettings['maxYearRange'])->toBe(25);
});
