<?php

declare(strict_types=1);

use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\db\Query;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260828_000000_page_conditions_mapping;

it('migrates page and next-button condition field references to field tokens', function (): void {
    $db = Craft::$app->getDb();
    $now = Db::prepareDateForDb(new DateTime());

    $db->createCommand()->insert(Table::FORMIE_FIELD_LAYOUTS, [
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ])->execute();
    $layoutId = (int)$db->getLastInsertID();

    $settings = [
        'pageConditions' => [
            'conditions' => [
                ['field' => 'fullName'],
                ['field' => '{email}'],
                ['field' => '[{group]'],
                ['field' => 'address[city]'],
                ['field' => '{submission:status}'],
            ],
        ],
        'nextButtonConditions' => [
            'conditions' => [
                ['field' => 'choice'],
            ],
        ],
    ];

    $db->createCommand()->insert(Table::FORMIE_FIELD_LAYOUT_PAGES, [
        'layoutId' => $layoutId,
        'label' => 'Conditions Page',
        'sortOrder' => 0,
        'settings' => Json::encode($settings),
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ])->execute();
    $pageId = (int)$db->getLastInsertID();

    $migration = new m260828_000000_page_conditions_mapping();
    expect($migration->safeUp())->toBeTrue();

    $updated = Json::decode((new Query())
        ->select(['settings'])
        ->from([Table::FORMIE_FIELD_LAYOUT_PAGES])
        ->where(['id' => $pageId])
        ->scalar()) ?? [];

    expect($updated['pageConditions']['conditions'][0]['field'])->toBe('{field:fullName}')
        ->and($updated['pageConditions']['conditions'][1]['field'])->toBe('{field:email}')
        ->and($updated['pageConditions']['conditions'][2]['field'])->toBe('{field:group}')
        ->and($updated['pageConditions']['conditions'][3]['field'])->toBe('{field:address.city}')
        ->and($updated['pageConditions']['conditions'][4]['field'])->toBe('{submission:status}')
        ->and($updated['nextButtonConditions']['conditions'][0]['field'])->toBe('{field:choice}');
});
