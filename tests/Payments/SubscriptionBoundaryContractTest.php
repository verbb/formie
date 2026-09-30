<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentCapabilityPurpose;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\helpers\PaymentCapabilities;
use verbb\formie\models\{PaymentFieldPayload, Subscription};
use verbb\formie\models\payments\{CancelSubscriptionCommand, SubscriptionSnapshot};
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\integrations\payments\GoCardless;

class BoundaryCancellationStripe extends Stripe
{
    public static int $cancellations = 0;
    public static bool $loseResponse = false;
    public function cancelSubscriptionSnapshot(Subscription $subscription, SubscriptionCancellationMode $mode): ?SubscriptionSnapshot {
        self::$cancellations++;
        if (self::$loseResponse) { throw new RuntimeException('Accepted remotely; response lost'); }
        return new SubscriptionSnapshot(
            SubscriptionStatus::CANCELLED,
            'canceled',
            reference: $subscription->reference,
            providerUpdatedAt: time(),
            cancelledAt: new DateTimeImmutable(),
            endedAt: new DateTimeImmutable(),
            cancellationMode: $mode,
            rawData: ['id' => $subscription->reference, 'status' => 'canceled'],
        );
    }
}

it('checks cancellation capability at the mutation boundary and never repeats an uncertain cancellation', function (bool $loseResponse): void {
    $integration = new BoundaryCancellationStripe(['name' => 'Cancellation', 'handle' => 'cancellation' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $subscriptions = Formie::$plugin->getSubscriptions();
    $one = new Subscription(['integrationId' => $integration->id, 'reference' => 'sub_one_' . uniqid(), 'status' => 'active']);
    $two = new Subscription(['integrationId' => $integration->id, 'reference' => 'sub_two_' . uniqid(), 'status' => 'active']);
    $subscriptions->saveSubscription($one);
    $subscriptions->saveSubscription($two);
    $scope = ['subscriptionUid' => $one->uid, 'integrationId' => $one->integrationId, 'submissionId' => $one->submissionId, 'cancellationMode' => SubscriptionCancellationMode::AT_PERIOD_END->value];
    $token = PaymentCapabilities::issue(PaymentCapabilityPurpose::CANCEL, $one->id, $scope, 1800);
    $expired = PaymentCapabilities::issue(PaymentCapabilityPurpose::CANCEL, $one->id, $scope, -1);
    $broad = PaymentCapabilities::issue(PaymentCapabilityPurpose::STATUS, $one->id, $scope, 1800);
    BoundaryCancellationStripe::$cancellations = 0;
    BoundaryCancellationStripe::$loseResponse = $loseResponse;
    foreach ([[$two, $token], [$one, $expired], [$one, $broad]] as [$target, $credential]) {
        expect(fn() => $subscriptions->cancelAuthorized($target, new CancelSubscriptionCommand($target->id, $credential)))->toThrow(\yii\web\ForbiddenHttpException::class);
    }
    expect(BoundaryCancellationStripe::$cancellations)->toBe(0);
    expect(fn() => $subscriptions->cancelAuthorized($one, new CancelSubscriptionCommand(
        $one->id,
        $token,
        SubscriptionCancellationMode::IMMEDIATE,
    )))->toThrow(\yii\web\ForbiddenHttpException::class);
    $command = new CancelSubscriptionCommand($one->id, $token);
    expect($subscriptions->cancelAuthorized($one, $command))->toBe(!$loseResponse);
    expect($subscriptions->getSubscriptionById($one->id)->status)->toBe($loseResponse ? 'unknown' : 'cancelled');
    expect($subscriptions->cancelAuthorized($one, $command))->toBe(!$loseResponse);
    expect(BoundaryCancellationStripe::$cancellations)->toBe(1);
    expect($subscriptions->getSubscriptionById($two->id)->status)->toBe('active');
})->with([false, true]);

it('allows an uncertain cancellation to be retried only after provider reconciliation proves it was not applied', function (): void {
    $integration = new BoundaryCancellationStripe(['name' => 'Cancellation reconciliation', 'handle' => 'cancelReconcile' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $subscriptions = Formie::$plugin->getSubscriptions();
    $subscription = new Subscription(['integrationId' => $integration->id, 'reference' => 'sub_reconcile_' . uniqid(), 'status' => 'active']);
    $subscriptions->saveSubscription($subscription);
    $scope = ['subscriptionUid' => $subscription->uid, 'integrationId' => $subscription->integrationId, 'submissionId' => $subscription->submissionId, 'cancellationMode' => SubscriptionCancellationMode::AT_PERIOD_END->value];
    $token = PaymentCapabilities::issue(PaymentCapabilityPurpose::CANCEL, $subscription->id, $scope, 1800);
    $command = new CancelSubscriptionCommand($subscription->id, $token);

    BoundaryCancellationStripe::$cancellations = 0;
    BoundaryCancellationStripe::$loseResponse = true;
    expect($subscriptions->cancelAuthorized($subscription, $command))->toBeFalse();
    expect($subscriptions->cancelAuthorized($subscription, $command))->toBeFalse();
    expect(BoundaryCancellationStripe::$cancellations)->toBe(1);

    $unknown = $subscriptions->getSubscriptionById($subscription->id);
    expect($unknown->canCancel)->toBeFalse();
    $reconciled = $subscriptions->applySnapshot($unknown, new SubscriptionSnapshot(
        SubscriptionStatus::ACTIVE,
        'active',
        reference: $unknown->reference,
        providerUpdatedAt: ($unknown->providerUpdatedAt ?? time()) + 1,
    ), 'providerReconciliation');
    expect($reconciled->scope['cancellationPending'])->toBeFalse()
        ->and($reconciled->scope['cancellationNotApplied'])->not->toBeEmpty()
        ->and($reconciled->canCancel)->toBeTrue();

    BoundaryCancellationStripe::$loseResponse = false;
    expect($subscriptions->cancelAuthorized($reconciled, $command))->toBeTrue();
    expect(BoundaryCancellationStripe::$cancellations)->toBe(2);
});

class BoundaryResumeStripe extends Stripe
{
    public array $payload = [];
    public array $remote = [];
    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload {
        return new PaymentFieldPayload($this->handle, 'payment', $this->payload);
    }
    public function getStripe(): \Stripe\StripeClient {
        return new class($this->remote) extends \Stripe\StripeClient {
            public function __construct(private array $remote) { parent::__construct('sk_test_fixture'); }
            public function __get($name) {
                if ($name !== 'subscriptions') { throw new RuntimeException('Resume must not create resources.'); }
                return new class($this->remote) {
                    public function __construct(private array $remote) {}
                    public function retrieve($id) { return \Stripe\Subscription::constructFrom(['id' => $id] + $this->remote); }
                };
            }
        };
    }
}

it('requires Stripe subscription resume ownership and maps current provider status', function (string $variant, string $expected): void {
    $integration = new BoundaryResumeStripe(['name' => 'Resume', 'handle' => 'resume' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => BoundaryResumeStripe::class,
        'providerSettings' => [$integration->handle => ['type' => 'subscription', 'currencyType' => 'fixed', 'currencyFixed' => 'USD', 'amountType' => 'fixed', 'amountFixed' => '25']]])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $subscription = Formie::$plugin->getPayments()->prepareSubscription($integration, $submission);
    $subscription->reference = 'sub_resume_' . uniqid();
    if ($variant === 'terminal') { $subscription->status = 'cancelled'; }
    Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    $integration->payload = ['stripeSubscriptionId' => $subscription->reference];
    $integration->remote = ['status' => in_array($variant, ['foreign', 'terminal'], true) ? 'active' : $variant];
    if ($variant === 'foreign') { $submission = formie()->submission($form)->save(); }
    $decision = $integration->processPayment($submission);
    expect($decision->status->value)->toBe($expected);
    $saved = Formie::$plugin->getSubscriptions()->getSubscriptionById($subscription->id);
    expect($saved->status)->toBe(match ($variant) { 'foreign' => 'pending', 'terminal' => 'cancelled', 'active' => 'active', 'trialing' => 'trialing', 'incomplete' => 'pending', 'past_due' => 'pastDue', 'incomplete_expired' => 'failed', default => 'unknown' });
})->with([['active', 'succeeded'], ['trialing', 'succeeded'], ['incomplete', 'pending'], ['past_due', 'pending'], ['incomplete_expired', 'cancelled'], ['foreign', 'unknown'], ['terminal', 'unknown']]);

it('retains Formie 3 subscription read projections while rejecting contradictory flag writes', function (): void {
    $subscription = new Subscription(['status' => 'active']);
    expect($subscription->hasStarted)->toBeTrue()->and($subscription->isCanceled)->toBeFalse();
    $subscription->status = 'cancelled';
    expect($subscription->isCanceled)->toBeTrue()->and($subscription->isExpired)->toBeFalse();
    expect(function () use ($subscription) { $subscription->isCanceled = false; })->toThrow(\yii\base\InvalidCallException::class);
});

it('applies provider snapshots once and rejects stale or terminal regressions', function (): void {
    $integration = new BoundaryCancellationStripe(['name' => 'Snapshots', 'handle' => 'snapshots' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $subscription = new Subscription(['integrationId' => $integration->id, 'reference' => 'sub_snapshot_' . uniqid()]);
    Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    $service = Formie::$plugin->getSubscriptions();

    $active = new SubscriptionSnapshot(SubscriptionStatus::ACTIVE, 'active', reference: $subscription->reference,
        providerUpdatedAt: 200, providerEventId: 'evt_active', currentPeriodEndsAt: new DateTimeImmutable('+1 month'));
    $saved = $service->applySnapshot($subscription, $active, 'test');
    expect($saved->status)->toBe('active')->and($saved->history)->toHaveCount(2);
    expect($service->applySnapshot($saved, $active, 'test')->history)->toHaveCount(2);

    $stale = new SubscriptionSnapshot(SubscriptionStatus::PAST_DUE, 'past_due', reference: $subscription->reference,
        providerUpdatedAt: 100, providerEventId: 'evt_stale');
    $saved = $service->applySnapshot($saved, $stale, 'test');
    expect($saved->status)->toBe('active')->and($saved->history)->toHaveCount(2);

    $cancelled = new SubscriptionSnapshot(SubscriptionStatus::CANCELLED, 'canceled', reference: $subscription->reference,
        providerUpdatedAt: 300, providerEventId: 'evt_cancelled', endedAt: new DateTimeImmutable());
    $saved = $service->applySnapshot($saved, $cancelled, 'test');
    $regression = new SubscriptionSnapshot(SubscriptionStatus::ACTIVE, 'active', reference: $subscription->reference,
        providerUpdatedAt: 400, providerEventId: 'evt_regression');
    expect($service->applySnapshot($saved, $regression, 'test')->status)->toBe('cancelled');
});

it('separates subscription setup operations from recurring monetary charges', function (): void {
    $integration = new BoundaryResumeStripe(['name' => 'Operation scope', 'handle' => 'scope' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => BoundaryResumeStripe::class,
        'providerSettings' => [$integration->handle => ['type' => 'subscription', 'currencyType' => 'fixed', 'currencyFixed' => 'USD', 'amountType' => 'fixed', 'amountFixed' => '25']]])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $subscription = Formie::$plugin->getPayments()->prepareSubscription($integration, $submission);
    $setup = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $recurring = Formie::$plugin->getPayments()->recordRecurring($subscription, 'invoice_' . uniqid(), '25', 'USD', 'success', []);

    expect($setup->getIsMonetary())->toBeFalse()->and($setup->getOperation())->toBe('subscriptionSetup');
    expect($recurring->getIsMonetary())->toBeTrue()->and($recurring->getOperation())->toBe('recurringCharge');
});

it('maps provider subscription states without inventing lifecycle states', function (string $provider, string $remote, SubscriptionStatus $expected): void {
    $integration = $provider === 'stripe' ? new Stripe() : new GoCardless();
    $method = new ReflectionMethod($integration, '_subscriptionSnapshot');
    $snapshot = $method->invoke($integration, ['id' => 'sub_fixture', 'status' => $remote, 'current_period_end' => time() + 3600]);

    expect($snapshot->status)->toBe($expected);
})->with([
    ['stripe', 'trialing', SubscriptionStatus::TRIALING],
    ['stripe', 'past_due', SubscriptionStatus::PAST_DUE],
    ['stripe', 'paused', SubscriptionStatus::PAUSED],
    ['stripe', 'incomplete_expired', SubscriptionStatus::FAILED],
    ['gocardless', 'active', SubscriptionStatus::ACTIVE],
    ['gocardless', 'customer_approval_denied', SubscriptionStatus::FAILED],
    ['gocardless', 'finished', SubscriptionStatus::COMPLETED],
]);

it('keeps scheduled cancellation separate from Stripe lifecycle state', function (): void {
    $method = new ReflectionMethod(Stripe::class, '_subscriptionSnapshot');
    $snapshot = $method->invoke(new Stripe(), [
        'id' => 'sub_scheduled',
        'status' => 'active',
        'cancel_at_period_end' => true,
        'current_period_end' => time() + 3600,
    ]);

    expect($snapshot->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($snapshot->cancellationMode)->toBe(SubscriptionCancellationMode::AT_PERIOD_END)
        ->and($snapshot->cancelAt)->not->toBeNull();
});

it('prevents removing payment integrations which still own manageable subscriptions', function (): void {
    $integration = new BoundaryCancellationStripe(['name' => 'Protected integration', 'handle' => 'protected' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $subscription = new Subscription(['integrationId' => $integration->id, 'reference' => 'sub_live_' . uniqid(), 'status' => 'active']);
    Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
    Formie::$plugin->getSubscriptions()->deleteSubscription($subscription);

    expect(Formie::$plugin->getIntegrations()->deleteIntegration($integration))->toBeFalse()
        ->and($integration->getErrors('id'))->not->toBeEmpty();
});

class BoundaryIntentStripe extends BoundaryResumeStripe
{
    public function getStripe(): \Stripe\StripeClient {
        return new class($this->remote) extends \Stripe\StripeClient {
            public function __construct(private array $remote) { parent::__construct('sk_test_fixture'); }
            public function __get($name) {
                if ($name !== 'paymentIntents') { throw new RuntimeException('Resume must not create resources.'); }
                return new class($this->remote) {
                    public function __construct(private array $remote) {}
                    public function retrieve($id) { return \Stripe\PaymentIntent::constructFrom(['id' => $id] + $this->remote); }
                };
            }
        };
    }
}

it('verifies Stripe intent resume money and preserves explicit provider outcomes', function (string $variant, string $expected): void {
    $integration = new BoundaryIntentStripe(['name' => 'Intent resume', 'handle' => 'intent' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => BoundaryIntentStripe::class,
        'providerSettings' => [$integration->handle => ['type' => 'single', 'currencyType' => 'fixed', 'currencyFixed' => 'USD', 'amountType' => 'fixed', 'amountFixed' => '25']]])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $payment = Formie::$plugin->getPayments()->prepareAttempt($integration, $submission);
    $payment->reference = 'pi_resume_' . uniqid();
    Formie::$plugin->getPayments()->savePayment($payment);
    $integration->payload = ['stripePaymentIntentId' => $payment->reference];
    $integration->remote = ['status' => in_array($variant, ['amount', 'currency', 'foreign'], true) ? 'succeeded' : $variant,
        'amount' => $variant === 'amount' ? 2501 : 2500, 'currency' => $variant === 'currency' ? 'eur' : 'usd', 'client_secret' => 'test_secret', 'last_payment_error' => null];
    if ($variant === 'foreign') { $submission = formie()->submission($form)->save(); }
    expect($integration->processPayment($submission)->status->value)->toBe($expected);
    if (in_array($variant, ['amount', 'currency', 'foreign'], true)) {
        expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->not->toBe('succeeded');
    }
})->with([['succeeded', 'succeeded'], ['processing', 'pending'], ['requires_action', 'requiresAction'], ['canceled', 'cancelled'], ['unrecognized', 'unknown'], ['amount', 'unknown'], ['currency', 'unknown'], ['foreign', 'unknown']]);
