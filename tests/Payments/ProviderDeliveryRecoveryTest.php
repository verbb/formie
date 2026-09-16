<?php

declare(strict_types=1);

use verbb\formie\integrations\payments\GoCardless;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Payment;

it('recovers a GoCardless idempotency conflict by fetching the accepted resource', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new class(['name' => 'Test', 'handle' => 'testGc']) extends GoCardless {
        public array $requests = [];
        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requests[] = [$method, $uri, $options];
            if ($method === 'POST') {
                throw new \GuzzleHttp\Exception\ClientException('Already accepted', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'),
                    new \GuzzleHttp\Psr7\Response(409, [], json_encode(['error' => ['errors' => [[
                        'reason' => 'idempotent_creation_conflict', 'links' => ['conflicting_resource_id' => 'PMexisting'],
                    ]]]])),
                );
            }
            return ['payments' => ['id' => 'PMexisting']];
        }
    };
    $payment = new Payment(['submissionId' => $submission->id, 'uid' => 'payment-test-uid']);
    $method = new ReflectionMethod(GoCardless::class, '_createResourceOnce');
    $result = $method->invoke($integration, $payment, 'payments', ['amount' => 100], '-pm-create');
    expect($result['payments']['id'])->toBe('PMexisting');
    $method->invoke($integration, $payment, 'payments', ['amount' => 100], '-pm-create');
    expect(array_column($integration->requests, 0))->toBe(['POST', 'GET', 'GET']);
    expect($integration->requests[0][2]['headers']['Idempotency-Key'])->toBe('payment-test-uid-pm-create');
});

it('recovers Stripe resources instead of creating them again', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $client = new class extends \Stripe\StripeClient {
        public object $resource;
        public function __construct() {
            parent::__construct('sk_test_fixture');
            $this->resource = new class {
                public array $keys = [];
                public int $reads = 0;
                public function create($payload, $options): object {
                    $this->keys[] = $options['idempotency_key'];
                    return (object)['id' => 'resource-existing'];
                }
                public function retrieve($id, $options): object {
                    $this->reads++;
                    return (object)['id' => $id];
                }
            };
        }
        public function __get($name) { return $this->resource; }
    };
    $integration = new class(['name' => 'Test', 'handle' => 'stripeTest']) extends Stripe {
        public \Stripe\StripeClient $testClient;
        public function getStripe(): \Stripe\StripeClient { return $this->testClient; }
    };
    $integration->testClient = $client;
    $method = new ReflectionMethod(Stripe::class, '_createResourceOnce');
    foreach (['customers', 'paymentIntents', 'subscriptions', 'subscriptionSchedules'] as $resource) {
        $method->invoke($integration, $submission, $resource, $resource . '-create', ['amount' => 100]);
        $method->invoke($integration, $submission, $resource, $resource . '-create', ['amount' => 100]);
    }
    expect($client->resource->keys)->toHaveCount(4)->and(array_unique($client->resource->keys))->toHaveCount(4);
    expect($client->resource->reads)->toBe(4);
});

it('reuses the Square payment key after a lost response and saves one successful payment', function (): void {
    $integration = new class(['name' => 'Square fixture', 'handle' => 'squareFixture']) extends \verbb\formie\integrations\payments\Square {
        public array $keys = [];
        public int $reads = 0;
        protected function getPaymentFieldPayload(\verbb\formie\elements\Submission $submission): \verbb\formie\models\PaymentFieldPayload
        {
            return new \verbb\formie\models\PaymentFieldPayload($this->handle, 'payment', ['squarePaymentId' => 'test-source']);
        }
        public function request(string $method, string $uri, array $options = []): mixed
        {
            if ($method === 'POST') {
                $this->keys[] = $options['json']['idempotency_key'];
                if (count($this->keys) === 1) {
                    throw new \GuzzleHttp\Exception\ConnectException('Response lost', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'));
                }
            } else {
                $this->reads++;
            }
            return ['payment' => ['id' => 'square-payment-1', 'status' => 'COMPLETED']];
        }
    };
    expect(\verbb\formie\Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => \verbb\formie\integrations\payments\Square::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    expect($integration->processPayment($submission)->status)->toBe('pending');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect($integration->keys)->toHaveCount(2)->and($integration->keys[0])->toBe($integration->keys[1]);
    expect($integration->reads)->toBe(1);
    expect((int)(new \craft\db\Query())->from(\verbb\formie\helpers\Table::FORMIE_PAYMENTS)
        ->where(['submissionId' => $submission->id, 'reference' => 'square-payment-1'])->count())->toBe(1);
});
