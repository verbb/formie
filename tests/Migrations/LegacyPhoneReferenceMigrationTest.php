<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\helpers\Table;
use verbb\formie\fields\Name;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\migrations\m260928_010000_reference_contract;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\migrations\m261027_000000_legacy_field_selectors;
use verbb\formie\references\ReferenceMigration;

it('canonicalizes only declared legacy phone selectors and retains token modifiers', function() {
    $form = formie()->form()->phoneField('phone')->create();
    $reference = $form->getFieldByHandle('phone')->reference;

    foreach (['number', 'countryCode'] as $selector) {
        $legacy = '{field:phone.' . $selector . ';transform=upper|Unknown}';
        $canonical = '{field:' . $reference . ':' . $selector . ';transform=upper|Unknown}';
        expect(ReferenceMigration::canonicalFieldTokens($form, $legacy))->toBe($canonical)
            ->and(ReferenceMigration::canonicalFieldTokens($form, $canonical))->toBe($canonical);
    }

    foreach (['{field:phone.missing}', '{field:missing.number}', '{field:phone.number:missing}', '{field:phone}'] as $token) {
        expect(ReferenceMigration::canonicalFieldTokens($form, $token))->toBe($token);
    }
});

it('migrates persisted legacy phone mappings into a complete integration payload', function(string $migration) {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->phoneField('phone')->create();
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $settings['integrations'] = ['fixture' => ['fieldMapping' => [
        'Name' => ['kind' => 'reference', 'value' => '{field:person}'],
        'Phone' => ['kind' => 'reference', 'value' => '{field:phone.number}'],
    ]]];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();

    foreach ([1, 2] as $run) {
        expect((new $migration())->safeUp())->toBeTrue();
        $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
        $mapping = $stored['integrations']['fixture']['fieldMapping'];
        expect($mapping['Phone']['value'])->toBe('{field:' . $form->getFieldByHandle('phone')->reference . ':number}');
    }

    $submission = formie()->submission($form)->with(['person' => ['firstName' => 'Jane', 'lastName' => 'Doe'], 'phone' => '0412345678'])->save();
    $destinations = array_map(fn($handle) => new IntegrationField(['handle' => $handle, 'type' => IntegrationField::TYPE_STRING]), ['Name', 'Phone']);
    $integration = new CampaignMonitor(['listId' => 'fixture']);
    $integration->cache['config'] = (new IntegrationConfig(['lists' => [new IntegrationCollection(['id' => 'fixture', 'fields' => $destinations])]], $integration->getIntegrationConfigKey(), time()))->toStorage();
    expect($integration->getFieldMappingValues($submission, $mapping))->toBe(['Name' => 'Jane Doe', 'Phone' => '0412345678']);
})->with([m260928_010000_reference_contract::class, m261027_000000_legacy_field_selectors::class]);
