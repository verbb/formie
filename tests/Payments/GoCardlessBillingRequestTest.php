<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\payments\GoCardless;
use verbb\formie\models\Payment as PaymentModel;
use Tests\Support\WebRequestTestHelper;

it('creates a billing request flow during payment processing', function(): void {
    $integration = new class([
        'name' => 'GoCardless Billing Request',
        'handle' => 'goCardlessBilling' . uniqid(),
        'accessToken' => 'test-token',
    ]) extends GoCardless {
        public array $requests = [];

        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requests[] = compact('method', 'uri', 'options');

            if ($method === 'POST' && $uri === 'billing_requests') {
                return [
                    'billing_requests' => [
                        'id' => 'BRQ123',
                        'status' => 'pending',
                        'metadata' => $options['json']['billing_requests']['metadata'] ?? [],
                    ],
                ];
            }

            if ($method === 'POST' && $uri === 'billing_request_flows') {
                return [
                    'billing_request_flows' => [
                        'id' => 'BRF123',
                        'authorisation_url' => 'https://pay.gocardless.com/billing/static/flow?id=BRF123',
                        'links' => [
                            'billing_request' => 'BRQ123',
                        ],
                    ],
                ];
            }

            return [];
        }
    };

    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);

    $form = formie()
        ->form(['title' => 'GoCardless Billing Request ' . uniqid()])
        ->singleLineTextField('fullName')
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => GoCardless::class,
            'providerSettings' => [
                $integration->handle => [
                    'currency' => 'GBP',
                    'amountType' => 'fixed',
                    'amountFixed' => 25,
                ],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Paddington Bear'])
        ->save();

    $field = $form->getFieldByHandle('payment');
    $integration->setField($field);

    $decision = WebRequestTestHelper::withWebRequestContext(
        fn() => $integration->processPayment($submission),
        ['referrer' => 'https://example.test/donate'],
    );

    expect($decision->status)->toBe('actionRequired')
        ->and($decision->action['payload']['redirectUrl'] ?? null)->toContain('pay.gocardless.com')
        ->and($integration->requests[0]['uri'])->toBe('billing_requests')
        ->and($integration->requests[0]['options']['json']['billing_requests']['mandate_request']['scheme'])->toBe('bacs')
        ->and($integration->requests[1]['uri'])->toBe('billing_request_flows');
});

it('creates a mandate payment after the billing request is fulfilled', function(): void {
    $integration = new GoCardless([
        'name' => 'GoCardless Sync',
        'handle' => 'goCardlessSync' . uniqid(),
        'accessToken' => 'test-token',
    ]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);

    $form = formie()
        ->form(['title' => 'GoCardless Sync ' . uniqid()])
        ->singleLineTextField('fullName')
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => GoCardless::class,
            'providerSettings' => [
                $integration->handle => [
                    'currency' => 'GBP',
                    'amountType' => 'fixed',
                    'amountFixed' => 25,
                ],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Payment Fixture'])
        ->save();

    $field = $form->getFieldByHandle('payment');
    $integration->setField($field);

    $payment = new PaymentModel([
        'integrationId' => $integration->id,
        'submissionId' => $submission->id,
        'fieldId' => $field->id,
        'amount' => 25.00,
        'currency' => 'GBP',
        'status' => PaymentModel::STATUS_REDIRECT,
        'redirectUrl' => 'https://example.test/donate',
        'response' => [
            'billingRequest' => [
                'id' => 'BRQ123',
                'status' => 'fulfilled',
                'mandate_request' => [
                    'links' => [
                        'mandate' => 'MD123',
                    ],
                ],
            ],
        ],
    ]);
    Formie::$plugin->getPayments()->savePayment($payment, false);
    $payment->response['billingRequest']['metadata'] = ['formiePaymentId' => (string)$payment->id];
    Formie::$plugin->getPayments()->savePayment($payment, false);

    $integration = new class([
        'id' => $integration->id,
        'name' => 'GoCardless Sync',
        'handle' => $integration->handle,
        'accessToken' => 'test-token',
    ]) extends GoCardless {
        public function request(string $method, string $uri, array $options = []): mixed
        {
            if ($method === 'GET' && $uri === 'billing_requests/BRQ123') {
                return [
                    'billing_requests' => [
                        'id' => 'BRQ123',
                        'status' => 'fulfilled',
                        'mandate_request' => [
                            'links' => [
                                'mandate' => 'MD123',
                            ],
                        ],
                    ],
                ];
            }

            if ($method === 'POST' && $uri === 'payments') {
                return [
                    'payments' => [
                        'id' => 'PM123',
                        'status' => 'pending_submission',
                        'metadata' => [
                            'formiePaymentId' => $options['json']['payments']['metadata']['formiePaymentId'] ?? null,
                        ],
                    ],
                ];
            }

            return [];
        }
    };
    $integration->setField($field);

    $integration->getTransactionStatus($payment);

    $payment = Formie::$plugin->getPayments()->getPaymentById((int)$payment->id);

    expect($payment->reference)->toBe('PM123')
        ->and($payment->status)->toBe(PaymentModel::STATUS_PENDING)
        ->and($payment->response['gcPayment']['id'] ?? null)->toBe('PM123');
});

it('resolves direct debit schemes from currency', function(): void {
    $integration = new GoCardless([
        'name' => 'GoCardless Scheme',
        'handle' => 'goCardlessScheme' . uniqid(),
    ]);

    $method = new ReflectionMethod(GoCardless::class, '_resolveDirectDebitScheme');
    $method->setAccessible(true);

    expect($method->invoke($integration, 'GBP'))->toBe('bacs')
        ->and($method->invoke($integration, 'EUR'))->toBe('sepa_core')
        ->and($method->invoke($integration, 'AUD'))->toBe('becs');
});
