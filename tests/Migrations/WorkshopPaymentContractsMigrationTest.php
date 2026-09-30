<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\{Payment, Subscription};
use verbb\formie\migrations\m260929_010000_payment_contracts;

it('migrates historical money and subscription evidence without guessing account ownership', function (): void {
    $db = Craft::$app->getDb();
    $payment = new Payment(['amount' => '19.999', 'currency' => 'BHD']);
    Formie::$plugin->getPayments()->savePayment($payment);
    $subscription = new Subscription();
    Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    $db->createCommand()->addColumn(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData', 'text')->execute();
    try {
        $db->createCommand()->update(Table::FORMIE_PAYMENTS, ['amountMinor' => null, 'status' => 'redirect', 'accountFingerprint' => null], ['id' => $payment->id])->execute();
        $db->createCommand()->update(Table::FORMIE_SUBSCRIPTIONS, ['subscriptionData' => Json::encode(['id' => 'sub-old', 'status' => 'active', 'customer_email' => 'historical@example.test', 'current_period_end' => 1900000000]), 'nextPaymentAt' => '2030-03-17 17:46:40'], ['id' => $subscription->id])->execute();
        $migration = new m260929_010000_payment_contracts();
        expect($migration->safeUp())->toBeTrue()->and($migration->safeUp())->toBeTrue();
        $saved = (new Query())->from(Table::FORMIE_PAYMENTS)->where(['id' => $payment->id])->one();
        expect($saved)->toMatchArray(['amountMinor' => '19999', 'status' => 'requiresAction', 'accountFingerprint' => null]);
        $row = (new Query())->from(Table::FORMIE_SUBSCRIPTIONS)->where(['id' => $subscription->id])->one();
        expect($row['providerData'])->not->toContain('historical@example.test')->and($row['nextPaymentAt'])->toBeNull();
        $cipher = (new Query())->select('evidence')->from('{{%formie_subscription_diagnostics}}')->where(['subscriptionUid' => $subscription->uid])->scalar();
        expect($cipher)->not->toContain('historical@example.test');
        $plain = Craft::$app->getSecurity()->decryptByKey(base64_decode($cipher), Formie::$plugin->getSettings()->getSecurityKey());
        expect(Json::decode($plain)['customer_email'])->toBe('historical@example.test');
    } finally {
        if ($db->columnExists(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData')) {
            $db->createCommand()->dropColumn(Table::FORMIE_SUBSCRIPTIONS, 'subscriptionData')->execute();
        }
        $db->createCommand()->delete(Table::FORMIE_PAYMENTS, ['id' => $payment->id])->execute();
        $db->createCommand()->delete('{{%formie_subscription_diagnostics}}', ['subscriptionUid' => $subscription->uid])->execute();
        $db->createCommand()->delete(Table::FORMIE_SUBSCRIPTIONS, ['id' => $subscription->id])->execute();
    }
});
