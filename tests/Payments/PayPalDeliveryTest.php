<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\PayPal;
use verbb\formie\models\PaymentFieldPayload;

class PayPalDeliveryFixture extends PayPal
{
    public array $requests = [];
    public string $authorizationId = 'AUTH-FIXTURE';
    public string $orderId = '';
    public string $authorizationAmount = '25.00';
    public string $authorizationCurrency = 'USD';
    public string $captureStatus = 'COMPLETED';
    public bool $loseResponse = false;
    public bool $wrongInvoice = false;
    public ?array $capture = null;

    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
    {
        return new PaymentFieldPayload($this->handle, 'payment', ['paypalAuthId' => $this->authorizationId, 'paypalOrderId' => $this->orderId]);
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $this->requests[] = [$method, $uri, $options];
        if ($uri === 'v1/oauth2/token') {
            return ['access_token' => 'mock-token', 'expires_in' => 3600];
        }
        expect($options['headers']['Authorization'])->toBe('Bearer mock-token');
        if (str_starts_with($uri, 'v2/checkout/orders/')) {
            return ['id' => $this->orderId, 'purchase_units' => [[
                'amount' => ['value' => '25.00', 'currency_code' => 'USD'],
                'payments' => ['authorizations' => [['id' => 'ORDER-AUTH']]],
            ]]];
        }
        if ($method === 'POST') {
            $this->capture ??= ['id' => 'CAPTURE-' . $this->uid,
                'amount' => $options['json']['amount'],
                'invoice_id' => $this->wrongInvoice ? 'another-submission' : $options['json']['invoice_id'],
                'status' => $this->captureStatus];
            if ($this->loseResponse) {
                $this->loseResponse = false;
                throw new \GuzzleHttp\Exception\ConnectException('Response lost', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'));
            }
            return $this->capture;
        }
        if (str_starts_with($uri, 'v2/payments/captures/')) {
            return array_merge($this->capture, ['status' => $this->captureStatus]);
        }
        return ['id' => $this->authorizationId, 'status' => 'CREATED',
            'amount' => ['value' => $this->authorizationAmount, 'currency_code' => $this->authorizationCurrency]];
    }
}

function paypalDeliveryFixture(): array
{
    $integration = new PayPalDeliveryFixture(['name' => 'PayPal recovery', 'handle' => 'paypal' . uniqid(),
        'scope' => 'site', 'clientId' => 'client-' . uniqid(), 'clientSecret' => 'mock-secret']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => PayPal::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    return [$integration, $submission, $form];
}

it('uses verified PayPal capture statuses instead of assuming success', function (string $providerStatus, string $decision): void {
    [$integration, $submission] = paypalDeliveryFixture();
    $integration->captureStatus = $providerStatus;
    expect($integration->processPayment($submission)->status)->toBe($decision);
    $posts = array_values(array_filter($integration->requests, fn($r) => str_ends_with($r[1], '/capture')));
    expect($posts[0][2]['json']['amount'])->toBe(['value' => '25.00', 'currency_code' => 'USD'])
        ->and($posts[0][2]['json']['final_capture'])->toBeTrue()
        ->and($posts[0][2]['headers']['PayPal-Request-Id'])->not->toBeEmpty();
})->with([['COMPLETED', 'succeeded'], ['PENDING', 'pending'], ['DECLINED', 'failed'], ['FAILED', 'failed'], ['REFUNDED', 'failed'], ['UNRECOGNIZED', 'pending']]);

it('rejects authorization amount or currency mismatches before capture', function (string $amount, string $currency): void {
    [$integration, $submission] = paypalDeliveryFixture();
    $integration->authorizationAmount = $amount;
    $integration->authorizationCurrency = $currency;
    expect($integration->processPayment($submission)->status)->toBe('failed')
        ->and(array_filter($integration->requests, fn($r) => str_ends_with($r[1], '/capture')))->toBeEmpty();
})->with([['1.00', 'USD'], ['25.00', 'AUD']]);

it('does not accept an authorization from a different approved order', function (): void {
    [$integration, $submission] = paypalDeliveryFixture();
    $integration->orderId = 'ORDER-FIXTURE';
    expect($integration->processPayment($submission)->status)->toBe('failed')
        ->and(array_filter($integration->requests, fn($r) => str_ends_with($r[1], '/capture')))->toBeEmpty();
});

it('reuses the capture key after a lost response and reconciles without another capture', function (): void {
    [$integration, $submission] = paypalDeliveryFixture();
    $integration->loseResponse = true;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    $posts = array_values(array_filter($integration->requests, fn($r) => str_ends_with($r[1], '/capture')));
    expect($posts)->toHaveCount(2)
        ->and($posts[0][2]['headers']['PayPal-Request-Id'])->toBe($posts[1][2]['headers']['PayPal-Request-Id'])
        ->and(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount(1);
});

it('does not credit a capture whose invoice belongs to another submission', function (): void {
    [$integration, $submission] = paypalDeliveryFixture();
    $integration->wrongInvoice = true;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
    expect($payments[0]->status)->toBe('processing');
});

it('binds an authorization to one submission before it can be captured', function (): void {
    [$integration, $submission, $form] = paypalDeliveryFixture();
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    $otherSubmission = formie()->submission($form)->save();
    expect($integration->processPayment($otherSubmission)->status)->toBe('failed')
        ->and(array_filter($integration->requests, fn($r) => str_ends_with($r[1], '/capture')))->toHaveCount(1);
});

it('reconciles a pending PayPal capture to a final status', function (): void {
    [$integration, $submission] = paypalDeliveryFixture();
    $integration->captureStatus = 'PENDING';
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $integration->captureStatus = 'COMPLETED';
    $integration->getTransaction($payment);
    expect($payment->status)->toBe('success')
        ->and(array_filter($integration->requests, fn($r) => str_ends_with($r[1], '/capture')))->toHaveCount(1);
});
