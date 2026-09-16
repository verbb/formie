<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260913_000001_integration_field_references;

it('migrates persisted integration tokens within their own form without altering unrelated settings or current tokens', function (): void {
    $expected = [];
    foreach ([1, 2] as $index) {
        $form = formie()->form()->emailField('email')->create();
        $reference = $form->getFieldByHandle('email')->reference;
        $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
        $settings['integrations'] = ['fixture' => ['enabled' => false, 'fieldMapping' => ['EMAIL' => '{field:email}', 'CURRENT' => '{field:' . $reference . '}', 'UNKNOWN' => '{field:missing}'], 'listId' => 'unchanged']];
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();
        $settings['integrations']['fixture']['fieldMapping']['EMAIL'] = '{field:' . $reference . '}';
        $expected[$form->id] = $settings;
    }
    foreach ([1, 2] as $run) {
        expect((new m260913_000001_integration_field_references())->safeUp())->toBeTrue();
        foreach ($expected as $id => $settings) {
            expect(Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $id])->scalar()))->toBe($settings);
        }
    }
});
