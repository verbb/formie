<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\{Payment, Subscription};
use Tests\Support\WebRequestTestHelper;

it('keeps successful Stripe payments terminal and binds signed events to their integration', function (): void {
    $integrations = [];
    foreach (['owner', 'other'] as $key) {
        $integration = new Stripe(['name' => $key, 'handle' => $key . uniqid(), 'webhookSecretKey' => 'whsec_' . $key]);
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
        $integrations[$key] = $integration;
    }
    $form = formie()->form()->singleLineTextField('message')->paymentField('payment', ['paymentIntegration' => $integrations['owner']->handle, 'paymentIntegrationType' => Stripe::class])->create();
    $submission = formie()->submission($form)->save();
    $payment = new Payment(['integrationId' => $integrations['owner']->id, 'submissionId' => $submission->id,
        'fieldId' => $form->getFieldByHandle('payment')->id,
        'amount' => 25, 'currency' => 'USD', 'status' => Payment::STATUS_SUCCESS, 'reference' => 'pi_boundary_' . uniqid()]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $send = function (Stripe $integration, string $status) use ($payment): void {
        $oldSignature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
        try {
            WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $status, $payment): void {
                $body = json_encode(['id' => 'evt_' . uniqid(), 'type' => 'payment_intent.processing', 'data' => ['object' => ['id' => $payment->reference, 'status' => $status]]]);
                $time = time();
                $_SERVER['HTTP_STRIPE_SIGNATURE'] = 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $body, $integration->webhookSecretKey);
                $request->setRawBody($body);
                expect($integration->processWebhook()->getStatusCode())->toBe(200);
            }, ['method' => 'POST']);
        } finally {
            if ($oldSignature === null) { unset($_SERVER['HTTP_STRIPE_SIGNATURE']); } else { $_SERVER['HTTP_STRIPE_SIGNATURE'] = $oldSignature; }
        }
    };
    $send($integrations['owner'], 'processing');
    expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_SUCCESS);
    $payment->status = Payment::STATUS_PENDING;
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $send($integrations['other'], 'processing');
    expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_PENDING);
    $send($integrations['owner'], 'processing');
    expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_PROCESSING);
})->group('security');

it('retrieves the current Stripe subscription period before persisting a paid invoice', function (): void {
    $integration = new class(['name' => 'Invoice fixture', 'handle' => 'invoice' . uniqid()]) extends Stripe {
        private ?\Stripe\StripeClient $_testClient = null;
        public function setTestClient(\Stripe\StripeClient $client): void { $this->_testClient = $client; }
        public function getStripe(): \Stripe\StripeClient { return $this->_testClient; }
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $client = new class extends \Stripe\StripeClient {
        public array $references = [];
        public function __construct() { parent::__construct('sk_test_local_only'); }
        public function __get($name) {
            if ($name !== 'subscriptions') { throw new RuntimeException('Unexpected Stripe service'); }
            return new class($this) {
                public function __construct(private object $client) {}
                public function retrieve($reference) {
                    $this->client->references[] = $reference;
                    return \Stripe\Subscription::constructFrom(['id' => $reference, 'current_period_end' => 1800000000]);
                }
            };
        }
    };
    $integration->setTestClient($client);
    $subscription = new Subscription(['trialDays' => 0, 'integrationId' => $integration->id, 'reference' => 'sub_boundary_' . uniqid()]);
    expect(Formie::$plugin->getSubscriptions()->saveSubscription($subscription))->toBeTrue();
    $method = new ReflectionMethod(Stripe::class, 'handleInvoiceSucceeded');
    $method->invoke($integration, ['id' => 'evt_paid', 'data' => ['object' => ['paid' => true, 'subscription' => $subscription->reference]]]);
    expect($client->references)->toBe([$subscription->reference]);
    expect(Formie::$plugin->getSubscriptions()->getSubscriptionById($subscription->id)->nextPaymentDate->getTimestamp())->toBe(1800000000);
});

it('returns a retryable failure when a valid Stripe webhook cannot be processed', function (): void {
    $integration = new class(['webhookSecretKey' => 'whsec_failure']) extends Stripe {
        protected function handlePaymentIntent(array $data): void { throw new RuntimeException('Synthetic persistence failure'); }
    };
    $oldSignature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration): void {
            $body = json_encode(['id' => 'evt_failure', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_failure', 'status' => 'succeeded']]]);
            $time = time();
            $_SERVER['HTTP_STRIPE_SIGNATURE'] = 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $body, 'whsec_failure');
            $request->setRawBody($body);
            $response = $integration->processWebhook();
            expect($response->getStatusCode())->toBe(500);
            expect($response->data)->toBe('error');
        }, ['method' => 'POST']);
    } finally {
        if ($oldSignature === null) { unset($_SERVER['HTTP_STRIPE_SIGNATURE']); } else { $_SERVER['HTTP_STRIPE_SIGNATURE'] = $oldSignature; }
    }
});

it('ignores subscription and invoice events authenticated by another integration', function (): void {
    $owner = new Stripe(['name' => 'Owner', 'handle' => 'owner' . uniqid()]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($owner, false))->toBeTrue();
    $other = new class(['name' => 'Other', 'handle' => 'other' . uniqid()]) extends Stripe {
        public function getStripe(): \Stripe\StripeClient { throw new RuntimeException('Must not call another integration API'); }
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($other, false))->toBeTrue();
    $subscription = new Subscription(['trialDays' => 0, 'integrationId' => $owner->id, 'reference' => 'sub_owned_' . uniqid()]);
    $subscriptions = Formie::$plugin->getSubscriptions();
    expect($subscriptions->saveSubscription($subscription))->toBeTrue();
    $data = ['id' => 'evt_cross', 'data' => ['object' => ['id' => $subscription->reference, 'subscription' => $subscription->reference, 'paid' => false, 'collection_method' => 'charge_automatically']]];
    foreach (['handleSubscriptionExpired', 'handleSubscriptionUpdated', 'handleInvoiceCreated', 'handleInvoiceFailed'] as $handler) {
        (new ReflectionMethod(Stripe::class, $handler))->invoke($other, $data);
    }
    expect($subscriptions->getSubscriptionById($subscription->id)->isExpired)->toBeFalse();
    expect($subscriptions->getSubscriptionById($subscription->id)->subscriptionData)->toBeNull();
    (new ReflectionMethod(Stripe::class, 'handleSubscriptionExpired'))->invoke($owner, $data);
    expect($subscriptions->getSubscriptionById($subscription->id)->isExpired)->toBeTrue();
})->group('security');
