<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\payments\PayWay;
use verbb\formie\elements\Submission;
use verbb\formie\models\PaymentFieldPayload;
use verbb\formie\helpers\Table;

function payWayDeliveryFixture(): array
{
    $integration = new class(['name' => 'PayWay fixture', 'handle' => 'payway' . uniqid(), 'secretKey' => 'fixture', 'merchantId' => 'TEST']) extends PayWay {
        public array $requests = [];
        public array $responseOverrides = [];
        public bool $loseResponse = false;
        public string $token = 'fixture-token';
        public int $submissionId = 0;
        protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
        {
            return new PaymentFieldPayload($this->handle, 'payment', ['paywayTokenId' => $this->token]);
        }
        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requests[] = [$method, $uri, $options];
            if ($this->loseResponse) {
                $this->loseResponse = false;
                throw new \GuzzleHttp\Exception\ConnectException('Response lost', new \GuzzleHttp\Psr7\Request($method, 'https://example.test'));
            }
            return array_replace([
                'transactionId' => 'payway-' . $this->submissionId,
                'transactionType' => 'payment', 'principalAmount' => 25,
                'currency' => 'aud', 'orderNumber' => (string)$this->submissionId,
                'customerNumber' => (string)$this->submissionId, 'status' => 'approved',
            ], $this->responseOverrides);
        }
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => PayWay::class,
        'providerSettings' => [$integration->handle => ['currency' => 'AUD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->submissionId = (int)$submission->id;
    $integration->setField($form->getFieldByHandle('payment'));
    return [$integration, $submission];
}

it('reuses the PayWay key after a lost response and reconciles one local payment', function (): void {
    [$integration, $submission] = payWayDeliveryFixture();
    $integration->loseResponse = true;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect(array_column($integration->requests, 0))->toBe(['POST', 'POST', 'GET'])
        ->and($integration->requests[0][2]['headers']['Idempotency-Key'])->toBe($integration->requests[1][2]['headers']['Idempotency-Key']);
    expect((int)(new \craft\db\Query())->from(Table::FORMIE_PAYMENTS)->where(['submissionId' => $submission->id])->count())->toBe(1);
});

it('does not accept mismatched PayWay transaction details', function (array $overrides): void {
    [$integration, $submission] = payWayDeliveryFixture();
    $integration->responseOverrides = $overrides;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    expect((int)(new \craft\db\Query())->from(Table::FORMIE_PAYMENTS)->where(['submissionId' => $submission->id, 'status' => 'success'])->count())->toBe(0);
})->with([
    'amount' => [['principalAmount' => 1]],
    'currency' => [['currency' => 'usd']],
    'submission' => [['orderNumber' => 'other']],
    'customer' => [['customerNumber' => 'other']],
    'type' => [['transactionType' => 'refund']],
]);

it('keeps PayWay pending and declined states distinct from success', function (string $remote, string $expected): void {
    [$integration, $submission] = payWayDeliveryFixture();
    $integration->responseOverrides = ['status' => $remote];
    expect($integration->processPayment($submission)->status)->toBe($expected);
})->with([['pending', 'pending'], ['suspended', 'pending'], ['declined', 'failed']]);

it('stops PayWay retries when the token or account changes after an uncertain write', function (string $property): void {
    [$integration, $submission] = payWayDeliveryFixture();
    $integration->loseResponse = true;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $integration->$property = 'changed';
    expect($integration->processPayment($submission)->status)->not->toBe('succeeded');
    expect($integration->requests)->toHaveCount(1);
})->with(['token', 'secretKey']);

it('reconciles a pending PayWay transaction without creating another charge', function (): void {
    [$integration, $submission] = payWayDeliveryFixture();
    $integration->responseOverrides = ['status' => 'pending'];
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $integration->responseOverrides = ['status' => 'approved'];
    $integration->getTransaction($payment);
    expect($payment->status)->toBe('success')
        ->and(array_column($integration->requests, 0))->toBe(['POST', 'GET']);
});
