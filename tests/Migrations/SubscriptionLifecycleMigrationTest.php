<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260928_020000_subscription_lifecycle;

beforeEach(function (): void {
    // Recreate only the historical column consumed by this older migration.
    if (!Craft::$app->getDb()->columnExists(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData')) {
        Craft::$app->getDb()->createCommand()->addColumn(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData', 'text')->execute();
    }
});

afterEach(function (): void {
    if (Craft::$app->getDb()->columnExists(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData')) {
        Craft::$app->getDb()->createCommand()->dropColumn(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData')->execute();
    }
});

it('migrates legacy subscription lifecycle and operation scope idempotently', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();
    $now = gmdate('Y-m-d H:i:s');
    $uid = StringHelper::UUID();
    Db::insert(Table::FORMIE_SUBSCRIPTIONS, [
        'status' => 'cancelling',
        'subscriptionData' => Json::encode([
            'id' => 'sub_migration_fixture',
            'status' => 'active',
            'cancel_at_period_end' => true,
            'current_period_end' => 1_900_000_000,
        ]),
        'trialDays' => 0,
        'version' => 0,
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => $uid,
    ]);
    $subscriptionId = (int)(new Query())->select('id')->from(Table::FORMIE_SUBSCRIPTIONS)->where(['uid' => $uid])->scalar();
    Db::insert(Table::FORMIE_PAYMENTS, [
        'subscriptionId' => $subscriptionId,
        'amount' => '25',
        'currency' => 'USD',
        'status' => 'success',
        'version' => 0,
        'reconciliationAttempts' => 0,
        'scope' => Json::encode(['initial' => true]),
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ]);

    try {
        $migration = new m260928_020000_subscription_lifecycle();
        expect($migration->safeUp())->toBeTrue();
        $first = (new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->where(['id' => $subscriptionId])->one();
        $payment = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['subscriptionId' => $subscriptionId])->one();
        $scope = Json::decode($payment['scope']);

        expect($first['status'])->toBe('active')
            ->and($first['providerStatus'])->toBe('active')
            ->and($first['cancellationMode'])->toBe('atPeriodEnd')
            ->and($first['nextPaymentAt'])->not->toBeNull()
            ->and($first['cancelAt'])->not->toBeNull()
            ->and($scope['monetary'])->toBeFalse()
            ->and($scope['operation'])->toBe('subscriptionSetup');

        expect($migration->safeUp())->toBeTrue();
        expect((new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->where(['id' => $subscriptionId])->one())->toMatchArray($first);
    } finally {
        $transaction->rollBack();
    }
});

it('does not invent setup or recurring semantics for Formie 3 subscription payments', function (): void {
    $transaction = Craft::$app->getDb()->beginTransaction();
    $now = gmdate('Y-m-d H:i:s');
    $subscriptionUid = StringHelper::UUID();
    Db::insert(Table::FORMIE_SUBSCRIPTIONS, [
        'status' => 'active',
        'subscriptionData' => Json::encode(['id' => 'sub_legacy_fixture', 'status' => 'active']),
        'trialDays' => 0,
        'version' => 0,
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => $subscriptionUid,
    ]);
    $subscriptionId = (int)(new Query())->select('id')->from(Table::FORMIE_SUBSCRIPTIONS)->where(['uid' => $subscriptionUid])->scalar();
    Db::insert(Table::FORMIE_PAYMENTS, [
        'subscriptionId' => $subscriptionId,
        'amount' => '25',
        'currency' => 'USD',
        'status' => 'success',
        'version' => 0,
        'reconciliationAttempts' => 0,
        'scope' => Json::encode(['legacy' => true]),
        'dateCreated' => $now,
        'dateUpdated' => $now,
        'uid' => StringHelper::UUID(),
    ]);

    try {
        expect((new m260928_020000_subscription_lifecycle())->safeUp())->toBeTrue();
        $payment = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['subscriptionId' => $subscriptionId])->one();
        $scope = Json::decode($payment['scope']);

        expect($scope['monetary'])->toBeTrue()
            ->and($scope['operation'])->toBe('legacySubscriptionPayment');
    } finally {
        $transaction->rollBack();
    }
});
