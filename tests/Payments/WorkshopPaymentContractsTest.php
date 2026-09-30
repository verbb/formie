<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\payments\Moneris;
use verbb\formie\models\{Payment, PaymentMoney, Subscription, SubscriptionPlan};
use verbb\formie\models\payments\SubscriptionSnapshot;

it('persists exact minor units while retaining the major-unit template projection', function (string $currency, string $major, string $minor): void {
    $payment = new Payment(['amount' => $major, 'currency' => $currency]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $saved = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($saved->amountMinor)->toBe($minor)->and($saved->amount)->toBe($major)
        ->and((new Query())->select('amountMinor')->from(Table::FORMIE_PAYMENTS)->where(['id' => $payment->id])->scalar())->toBe($minor);
    expect(fn() => PaymentMoney::fromDecimal($major . ($currency === 'JPY' ? '.1' : '1'), $currency))->toThrow(InvalidArgumentException::class);
})->with([['USD', '19.99', '1999'], ['BHD', '19.999', '19999'], ['JPY', '19', '19']]);

it('keeps account identity stable across credential rotation but changes with account or environment', function (): void {
    $integration = new Moneris(['uid' => 'account-fixture', 'storeId' => 'store-one', 'apiToken' => 'credential-one']);
    $fingerprint = $integration->getPaymentAccountFingerprint();
    $integration->apiToken = 'credential-two';
    expect($integration->getPaymentAccountFingerprint())->toBe($fingerprint);
    $integration->storeId = 'store-two';
    expect($integration->getPaymentAccountFingerprint())->not->toBe($fingerprint);
    $integration->storeId = 'store-one';
    $integration->useSandbox = true;
    expect($integration->getPaymentAccountFingerprint())->not->toBe($fingerprint);
});

it('scopes identical provider plan references and snapshots immutable commercial terms', function (): void {
    $integration = new Moneris(['name' => 'Plan owner', 'handle' => 'planOwner' . uniqid(), 'storeId' => 'store-one']);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $plans = Formie::$plugin->getPlans();
    $reference = 'same-price-' . uniqid();
    $first = new SubscriptionPlan(['integrationId' => $integration->id, 'accountFingerprint' => str_repeat('a', 64), 'reference' => $reference,
        'handle' => 'same-name', 'enabled' => true, 'isArchived' => false, 'amountMinor' => '2500', 'currency' => 'USD', 'interval' => 'month', 'intervalCount' => 1]);
    $second = clone $first;
    $second->accountFingerprint = str_repeat('b', 64);
    expect($plans->savePlan($first))->toBeTrue()->and($plans->savePlan($second))->toBeTrue();
    expect($plans->getPlanByReference($reference))->toBeNull()
        ->and($plans->getPlanByReference($reference, $integration->id, $first->accountFingerprint)->id)->toBe($first->id)
        ->and($plans->getPlanByReference($reference, $integration->id, $second->accountFingerprint)->id)->toBe($second->id);
    $subscription = new Subscription(['integrationId' => $integration->id, 'planId' => $first->id, 'accountFingerprint' => $first->accountFingerprint]);
    Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    $first->amountMinor = '9900';
    $plans->savePlan($first);
    $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionById($subscription->id);
    $subscription->terms = ['amountMinor' => '9900'];
    Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    expect($subscription->terms)->toMatchArray(['amountMinor' => '2500', 'currency' => 'USD', 'interval' => 'month', 'intervalCount' => 1]);
    $first->accountFingerprint = str_repeat('c', 64);
    $first->reference = 'different-account-price';
    $plans->savePlan($first);
    expect($first->accountFingerprint)->toBe(str_repeat('a', 64))->and($first->reference)->toBe($reference);
});

it('retains encrypted provider evidence separately from curated state and distinct synchronization dates', function (): void {
    $subscription = new Subscription();
    $service = Formie::$plugin->getSubscriptions();
    $service->saveSubscription($subscription);
    $periodEnd = new DateTimeImmutable('+1 month');
    $raw = ['id' => 'sub-test', 'status' => 'active', 'customer_email' => 'private@example.test', 'secret' => 'private-secret', 'current_period_end' => $periodEnd->getTimestamp()];
    $saved = $service->applySnapshot($subscription, new SubscriptionSnapshot(SubscriptionStatus::ACTIVE, 'active', reference: 'sub-test', providerUpdatedAt: 1700000000, currentPeriodEndsAt: $periodEnd, rawData: $raw));
    expect($saved->providerData)->not->toHaveKeys(['customer_email', 'secret', 'current_period_end'])
        ->and($saved->nextPaymentAt)->toBeNull()->and($saved->currentPeriodEndsAt->getTimestamp())->toBe($periodEnd->getTimestamp())
        ->and($saved->lastSyncedAt->getTimestamp())->toBeGreaterThan($saved->providerUpdatedAt);
    $row = (new Query())->from('{{%formie_subscription_diagnostics}}')->where(['subscriptionUid' => $saved->uid])->one();
    expect($row['evidence'])->not->toContain('private@example.test');
    $evidence = Craft::$app->getSecurity()->decryptByKey(base64_decode($row['evidence']), Formie::$plugin->getSettings()->getSecurityKey());
    expect(Json::decode($evidence))->toBe($raw);
    Craft::$app->getDb()->createCommand()->update('{{%formie_subscription_diagnostics}}', ['observedAt' => '2000-01-01 00:00:00'], ['id' => $row['id']])->execute();
    Formie::$plugin->getDeliveryAttempts()->purgeExpiredEvidence();
    expect((new Query())->from('{{%formie_subscription_diagnostics}}')->where(['id' => $row['id']])->exists())->toBeFalse();
});

it('keeps UI actions out of the authoritative payment status and rejects undeclared states', function (): void {
    $payment = new Payment(['status' => 'redirect']);
    expect($payment->getState())->toBe(\verbb\formie\enums\PaymentStatus::REQUIRES_ACTION)
        ->and($payment->status)->toBe('requiresAction')
        ->and($payment->toArray())->toMatchArray(['status' => 'requiresAction', 'amountMinor' => '0']);
    expect(fn() => $payment->status = 'invented')->toThrow(ValueError::class);
});

it('binds an unverified historical payment only through explicit operator verification', function (): void {
    $integration = new Moneris(['name' => 'Historical account', 'handle' => 'historical' . uniqid(), 'storeId' => 'original-store']);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $payment = new Payment(['integrationId' => $integration->id, 'amount' => '12.34', 'currency' => 'USD', 'status' => 'unknown']);
    Formie::$plugin->getPayments()->savePayment($payment);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PAYMENTS, ['accountFingerprint' => null], ['id' => $payment->id])->execute();
    expect(fn() => \verbb\formie\helpers\PaymentRecovery::verifyLegacyAccount('payment', $payment->id, ''))->toThrow(RuntimeException::class);
    \verbb\formie\helpers\PaymentRecovery::verifyLegacyAccount('payment', $payment->id, 'Original store independently verified.');
    $saved = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($saved->accountFingerprint)->toBe($integration->getPaymentAccountFingerprint())
        ->and($saved->status)->toBe('unknown')->and($saved->amountMinor)->toBe('1234')
        ->and($saved->scope['accountVerification']['source'])->toBe('operator');
    expect(fn() => \verbb\formie\helpers\PaymentRecovery::verifyLegacyAccount('payment', $payment->id, 'Try replacing owner.'))->toThrow(RuntimeException::class);
});

it('conservatively binds unannotated legacy credentials when an account ID is unavailable', function (): void {
    $integration = new class extends \verbb\formie\integrations\payments\Mollie {
        public string $legacyCredential = 'first';
    };
    $fingerprint = $integration->getPaymentAccountFingerprint();
    $integration->legacyCredential = 'second';
    expect($integration->getPaymentAccountFingerprint())->not->toBe($fingerprint);
});

it('reuses a prepared payment after same-account credential rotation and rejects an account switch', function (): void {
    $integration = new Moneris(['name' => 'Stable account', 'handle' => 'stable' . uniqid(), 'storeId' => 'original-store', 'apiToken' => 'old-token']);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => Moneris::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]]])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $service = Formie::$plugin->getPayments();
    $payment = $service->prepareAttempt($integration, $submission);
    $integration->apiToken = 'new-token';
    expect($service->prepareAttempt($integration, $submission)->id)->toBe($payment->id);
    $integration->storeId = 'different-store';
    expect(fn() => $service->prepareAttempt($integration, $submission))->toThrow(\verbb\formie\errors\DeliveryOutcomeUnknownException::class);
    expect($service->getSubmissionPayments($submission))->toHaveCount(1);
});
