<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\PaymentCapabilities;
use verbb\formie\models\{PaymentFieldPayload, Subscription};
use verbb\formie\models\payments\CancelSubscriptionCommand;
use verbb\formie\integrations\payments\Stripe;

class BoundaryCancellationStripe extends Stripe
{
    public static int $cancellations = 0;
    public static bool $loseResponse = false;
    public function cancelSubscription($reference, $params = []): ?array {
        self::$cancellations++;
        if (self::$loseResponse) { throw new RuntimeException('Accepted remotely; response lost'); }
        $subscription = Formie::$plugin->getSubscriptions()->getSubscriptionByReference($reference, $this->id);
        $subscription->status = 'cancelled';
        Formie::$plugin->getSubscriptions()->saveSubscription($subscription);
        return ['id' => $reference, 'status' => 'canceled'];
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
    $scope = ['subscriptionUid' => $one->uid, 'integrationId' => $one->integrationId, 'submissionId' => $one->submissionId];
    $token = PaymentCapabilities::issue('cancel', $one->id, $scope, 1800);
    $expired = PaymentCapabilities::issue('cancel', $one->id, $scope, -1);
    $broad = PaymentCapabilities::issue('status', $one->id, $scope, 1800);
    BoundaryCancellationStripe::$cancellations = 0;
    BoundaryCancellationStripe::$loseResponse = $loseResponse;
    foreach ([[$two, $token], [$one, $expired], [$one, $broad]] as [$target, $credential]) {
        expect(fn() => $subscriptions->cancelAuthorized($target, new CancelSubscriptionCommand($target->id, $credential)))->toThrow(\yii\web\ForbiddenHttpException::class);
    }
    expect(BoundaryCancellationStripe::$cancellations)->toBe(0);
    $command = new CancelSubscriptionCommand($one->id, $token);
    expect($subscriptions->cancelAuthorized($one, $command))->toBe(!$loseResponse);
    expect($subscriptions->getSubscriptionById($one->id)->status)->toBe($loseResponse ? 'unknown' : 'cancelled');
    expect($subscriptions->cancelAuthorized($one, $command))->toBe(!$loseResponse);
    expect(BoundaryCancellationStripe::$cancellations)->toBe(1);
    expect($subscriptions->getSubscriptionById($two->id)->status)->toBe('active');
})->with([false, true]);

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
    expect($saved->status)->toBe(match ($variant) { 'foreign' => 'pending', 'terminal' => 'cancelled', 'active', 'trialing' => 'active', 'incomplete' => 'pending', 'past_due' => 'suspended', 'incomplete_expired' => 'expired', default => 'unknown' });
})->with([['active', 'succeeded'], ['trialing', 'succeeded'], ['incomplete', 'pending'], ['past_due', 'pending'], ['incomplete_expired', 'cancelled'], ['foreign', 'unknown'], ['terminal', 'unknown']]);

it('retains Formie 3 subscription read projections while rejecting contradictory flag writes', function (): void {
    $subscription = new Subscription(['status' => 'active']);
    expect($subscription->hasStarted)->toBeTrue()->and($subscription->isCanceled)->toBeFalse();
    $subscription->status = 'cancelled';
    expect($subscription->isCanceled)->toBeTrue()->and($subscription->isExpired)->toBeFalse();
    expect(function () use ($subscription) { $subscription->isCanceled = false; })->toThrow(\yii\base\InvalidCallException::class);
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
        expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->not->toBe('success');
    }
})->with([['succeeded', 'succeeded'], ['processing', 'pending'], ['requires_action', 'actionRequired'], ['canceled', 'cancelled'], ['unrecognized', 'unknown'], ['amount', 'unknown'], ['currency', 'unknown'], ['foreign', 'unknown']]);
