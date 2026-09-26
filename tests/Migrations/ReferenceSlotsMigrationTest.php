<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260926_000000_reference_slots;

it('migrates stored mappings idempotently while preserving unrelated settings and unknown tokens', function() {
    $form = formie()->form()->singleLineTextField('value')->create();
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $settings['integrations'] = ['fixture' => ['fieldMapping' => ['KNOWN' => '{field:value}', 'UNKNOWN' => '{field:deleted}', 'LITERAL' => 'x'], 'endpoint' => 'unchanged']];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();
    foreach ([1, 2] as $run) {
        expect((new m260926_000000_reference_slots())->safeUp())->toBeTrue();
        $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
        expect($stored['integrations']['fixture']['fieldMapping']['KNOWN'])->toBe(['kind' => 'reference', 'value' => '{field:value}'])
            ->and($stored['integrations']['fixture']['fieldMapping']['UNKNOWN'])->toBe(['kind' => 'reference', 'value' => '{field:deleted}'])
            ->and($stored['integrations']['fixture']['fieldMapping']['LITERAL'])->toBe(['kind' => 'literal', 'value' => 'x'])
            ->and($stored['integrations']['fixture']['endpoint'])->toBe('unchanged');
    }
});
