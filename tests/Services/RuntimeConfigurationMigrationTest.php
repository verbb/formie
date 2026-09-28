<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260927_010000_instance_configuration;

it('migrates legacy stored completion and field settings idempotently without resaving submissions', function (): void {
    $form = formie()->form()->hiddenField('legacy')->create();
    $field = $form->getFieldByHandle('legacy');
    $submission = formie()->submission($form)->with(['legacy' => 'existing'])->save();
    $before = (new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->one();
    $db = Craft::$app->getDb();
    $legacy = Json::encode(['defaultOption' => 'dateInt', 'prePopulate' => 'oldQuery']);
    $db->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => $legacy], ['id' => $field->definitionId])->execute();
    $db->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['settings' => $legacy], ['id' => $field->id])->execute();
    $db->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode(['submitAction' => 'url', 'redirectUrl' => '/legacy'])], ['id' => $form->id])->execute();
    $migration = new m260927_010000_instance_configuration();
    expect($migration->safeUp())->toBeTrue()->and($migration->safeUp())->toBeTrue();
    foreach ([Table::FORMIE_FIELDS => $field->definitionId, Table::FORMIE_FORM_FIELDS => $field->id] as $table => $id) {
        $settings = Json::decode((new Query())->select('settings')->from($table)->where(['id' => $id])->scalar());
        expect($settings)->toBe(['prefillQueryParam' => 'oldQuery', 'valueSource' => 'dateInt']);
    }
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    expect($settings['completionBehavior'])->toBe('redirect')->and($settings['completionRedirectSource'])->toBe('url');
    expect((new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->one())->toBe($before);
});

it('does not treat nested integration settings as completion settings', function (): void {
    $migrated = \verbb\formie\helpers\RuntimeConfigurationMigration::migrate([
        'submitAction' => 'url',
        'submitActionUrl' => '/thanks',
        'integrations' => [
            'example' => [
                'submitAction' => 'provider-action',
                'submitActionUrl' => 'provider-owned-value',
            ],
        ],
    ]);

    expect($migrated['completionBehavior'])->toBe('redirect')
        ->and($migrated['redirectUrl'])->toBe('/thanks')
        ->and($migrated['integrations']['example'])->toBe([
            'submitAction' => 'provider-action',
            'submitActionUrl' => 'provider-owned-value',
        ]);
});
