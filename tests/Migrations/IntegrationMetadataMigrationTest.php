<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\migrations\m260927_000000_delivery_attempts;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;

it('keeps historical provider metadata and connection state usable across the delivery migration', function (): void {
    $form = formie()->form()->emailField('email')->create();
    $submission = formie()->submission($form)->with(['email' => 'upgrade@example.test'])->save();
    $integration = new Mailchimp(['name' => 'Historical metadata', 'handle' => 'historicalMetadata', 'apiKey' => 'fixture-key-us1']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    // This is the serialized metadata written by Formie 3 after provider discovery.
    $cache = ['connection' => 'success', 'settings' => ['apiKey' => 'must-not-be-exposed', 'lists' => [
        ['class' => IntegrationCollection::class, 'id' => 'fixture-list', 'name' => 'Fixture list', 'fields' => [
            ['class' => IntegrationField::class, 'handle' => 'email_address', 'name' => 'Email', 'type' => 'string'],
        ]],
    ]]];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_INTEGRATIONS, ['cache' => Json::encode($cache)], ['id' => $integration->id])->execute();
    $migration = new m260927_000000_delivery_attempts();

    expect($migration->safeUp())->toBeTrue()
        ->and($migration->safeUp())->toBeTrue();

    $row = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one();
    expect(Json::decode($row['cache']))->toBe($cache);
    unset($row['dateDeleted']);
    $reloaded = Formie::$plugin->getIntegrations()->createIntegration($row);
    $reloaded->listId = 'fixture-list';

    expect($reloaded->getIsConnected())->toBeTrue()
        ->and($reloaded->getConfig()->isStale())->toBeTrue()
        ->and($reloaded->getConfig()->get('lists')[0]->fields[0])->toBeInstanceOf(IntegrationField::class)
        ->and(Json::encode($reloaded->getConfig()->toStorage()))->not->toContain('must-not-be-exposed')
        ->and($reloaded->getFieldMappingValues($submission, ['email_address' => ['kind' => 'reference', 'value' => '{field:email}']]))->toBe(['email_address' => 'upgrade@example.test']);
});
