<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\Opayo;
use verbb\formie\integrations\payments\Paddle;
use verbb\formie\models\Payment;
use verbb\formie\models\PaymentFieldPayload;

function checkoutVerificationFixture(string $provider): array
{
    $integration = $provider === 'paddle'
        ? new class(['name' => 'Paddle verification', 'handle' => 'paddleVerification' . bin2hex(random_bytes(5))]) extends Paddle {
            public array $payload = [];
            public array $requests = [];
            public array $transaction = [];
            public ?string $loseResponseFor = null;
            protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
            {
                return new PaymentFieldPayload($this->handle, 'payment', $this->payload);
            }
            public function request(string $method, string $uri, array $options = []): mixed
            {
                $this->requests[] = [$method, $uri, $options];
                if ($method === 'GET') {
                    if (str_starts_with($uri, 'products/') || str_starts_with($uri, 'prices/')) {
                        return ['data' => ['id' => basename($uri)]];
                    }
                    return ['data' => $this->transaction];
                }
                if ($uri === $this->loseResponseFor) {
                    throw new \GuzzleHttp\Exception\ConnectException('Response lost', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'));
                }
                return ['data' => ['id' => match ($uri) {
                    'products' => 'pro_' . str_repeat('a', 26),
                    'prices' => 'pri_' . str_repeat('b', 26),
                    'transactions' => 'txn_' . str_repeat('c', 26),
                    default => throw new RuntimeException('Unexpected request: ' . $uri),
                }]];
            }
        }
        : new class(['name' => 'Opayo verification', 'handle' => 'opayoVerification' . bin2hex(random_bytes(5))]) extends Opayo {
            public array $payload = [];
            public array $requests = [];
            public array $response = [];
            public ?Throwable $exception = null;
            protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
            {
                return new PaymentFieldPayload($this->handle, 'payment', $this->payload);
            }
            public function request(string $method, string $uri, array $options = []): mixed
            {
                $this->requests[] = [$method, $uri, $options];
                if ($this->exception) { throw $this->exception; }
                if ($this->response) { return $this->response; }
                throw new RuntimeException('Continuation must not charge a card.');
            }
        };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => $provider === 'paddle' ? Paddle::class : Opayo::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $field = $form->getFieldByHandle('payment');
    $integration->setField($field);

    return [$integration, $submission, $field];
}

it('does not accept browser checkout data as proof of a Paddle payment', function (): void {
    [$integration, $submission] = checkoutVerificationFixture('paddle');
    $integration->payload = ['paddleCheckoutData' => ['id' => 'forged-checkout', 'transaction_id' => 'txn_' . str_repeat('a', 26)]];
    expect($integration->processPayment($submission)->status)->toBe('failed');
    expect(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toBe([]);
});

it('requires a successful Opayo payment belonging to the current submission and field', function (string $difference): void {
    [$integration, $submission, $field] = checkoutVerificationFixture('opayo');
    $payment = new Payment([
        'integrationId' => $integration->id,
        'submissionId' => $submission->id,
        'fieldId' => $field->id,
        'amount' => 25,
        'currency' => 'USD',
        'status' => Payment::STATUS_SUCCESS,
        'reference' => 'opayo-' . bin2hex(random_bytes(10)),
    ]);
    if ($difference === 'pending') { $payment->status = Payment::STATUS_PENDING; }
    if ($difference === 'failed') { $payment->status = Payment::STATUS_FAILED; }
    if ($difference === 'submission') { $payment->submissionId = formie()->submission($submission->getForm())->save()->id; }
    if ($difference === 'field') { $payment->fieldId = checkoutVerificationFixture('opayo')[2]->id; }
    if ($difference === 'integration') { $payment->integrationId = checkoutVerificationFixture('opayo')[0]->id; }
    if ($difference === 'amount') { $payment->amount = 1; }
    if ($difference === 'currency') { $payment->currency = 'EUR'; }
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $integration->payload = ['opayo3DSComplete' => $payment->reference];

    expect($integration->processPayment($submission)->status)->toBe($difference === 'valid' ? 'succeeded' : 'failed');
    expect($integration->requests)->toBe([]);
    $stored = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($stored->status)->toBe($payment->status)
        ->and($stored->submissionId)->toBe($payment->submissionId)
        ->and($stored->amount)->toBe($payment->amount);
})->with(['valid', 'pending', 'failed', 'submission', 'field', 'integration', 'amount', 'currency']);

it('creates one Paddle transaction and verifies its server response before completing the same payment', function (string $variant): void {
    [$integration, $submission, $field] = checkoutVerificationFixture('paddle');
    $decision = $integration->processPayment($submission);
    expect($decision->status)->toBe('actionRequired');
    $rows = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
    expect($rows)->toHaveCount(1);
    $payment = $rows[0];
    $reference = 'txn_' . str_repeat('c', 26);
    expect($payment->reference)->toBe($reference)
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($decision->action['payload']['transactionId'])->toBe($reference)
        ->and($decision->action['payload'])->not->toHaveKey('items');
    $repeated = $integration->processPayment($submission);
    expect($repeated->action['payload'])->toBe($decision->action['payload']);
    expect(array_column($integration->requests, 1))->toBe(['products', 'prices', 'transactions']);
    expect($integration->requests[2][2]['json']['items'])->toBe([['price_id' => 'pri_' . str_repeat('b', 26), 'quantity' => 1]]);

    $integration->payload = ['paddleCheckoutData' => ['transaction_id' => $reference]];
    $integration->transaction = [
        'id' => $reference, 'status' => 'completed', 'currency_code' => 'USD',
        'items' => [['quantity' => 1, 'price' => ['unit_price' => ['amount' => '2500', 'currency_code' => 'USD']]]],
        'details' => ['totals' => ['total' => '2500']],
    ];
    if ($variant === 'paid') { $integration->transaction['status'] = 'paid'; }
    if ($variant === 'tax') { $integration->transaction['details']['totals']['total'] = '2750'; }
    if (in_array($variant, ['draft', 'ready', 'canceled', 'past_due'], true)) { $integration->transaction['status'] = $variant; }
    if ($variant === 'currency') { $integration->transaction['currency_code'] = 'EUR'; }
    if ($variant === 'underpaid') { $integration->transaction['details']['totals']['total'] = '100'; }
    if ($variant === 'price') { $integration->transaction['items'][0]['price']['unit_price']['amount'] = '100'; }
    if ($variant === 'quantity') { $integration->transaction['items'][0]['quantity'] = 2; }
    if ($variant === 'reference') { $integration->transaction['id'] = 'txn_' . str_repeat('d', 26); }
    if ($variant === 'missing-total') { unset($integration->transaction['details']); }
    if ($variant === 'borrowed-checkout') { $integration->payload['paddleCheckoutData']['transaction_id'] = 'txn_' . str_repeat('d', 26); }
    if ($variant === 'changed-amount') { $field->providerSettings[$integration->handle]['amountFixed'] = 30; }

    $valid = in_array($variant, ['completed', 'paid', 'tax'], true);
    expect($integration->processPayment($submission)->status)->toBe($valid ? 'succeeded' : 'failed');
    $saved = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($saved->status)->toBe($valid ? Payment::STATUS_SUCCESS : Payment::STATUS_PENDING)
        ->and($saved->submissionId)->toBe($submission->id)
        ->and($saved->amount)->toBe(25.0);
    expect(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount(1);
    if ($valid) {
        expect($integration->processPayment($submission)->status)->toBe('succeeded');
        expect(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount(1);
    }
})->with(['completed', 'paid', 'tax', 'draft', 'ready', 'canceled', 'past_due', 'currency', 'underpaid', 'price', 'quantity', 'reference', 'missing-total', 'borrowed-checkout', 'changed-amount']);

it('binds Opayo callbacks to a valid payment capability and preserves the stored owner on errors', function (string $variant): void {
    [$integration, $submission, $field] = checkoutVerificationFixture('opayo');
    $payment = new Payment([
        'integrationId' => $integration->id, 'submissionId' => $submission->id, 'fieldId' => $field->id,
        'amount' => 25, 'currency' => 'USD', 'status' => Payment::STATUS_PENDING,
        'reference' => 'opayo-' . bin2hex(random_bytes(10)),
    ]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $token = \verbb\formie\helpers\PaymentAccess::issueStatusToken($payment, $variant === 'expired' ? time() - 90000 : null);
    if ($variant === 'unsigned') {
        $token = base64_encode(json_encode(['reference' => $payment->reference, 'submissionId' => $submission->id + 1, 'amount' => 1, 'currency' => 'EUR']));
    }
    if ($variant === 'integration') { $integration = checkoutVerificationFixture('opayo')[0]; }
    $integration->response = ['status' => 'Ok', 'transactionId' => $payment->reference];
    if ($variant === 'declined') { $integration->response['status'] = 'NotAuthed'; }
    if ($variant === 'reference') { $integration->response['transactionId'] = 'different-transaction'; }
    if ($variant === '1017') {
        $integration->exception = new \GuzzleHttp\Exception\ClientException('Operation not allowed', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'), new \GuzzleHttp\Psr7\Response(400, [], '{"code":1017}'));
    }

    $invoke = function () use ($integration, $token) {
        return \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => $integration->processCallback()->data, [
            'method' => 'POST', 'bodyParams' => ['cres' => 'synthetic-challenge', 'threeDSSessionData' => $token],
        ]);
    };
    if (in_array($variant, ['unsigned', 'expired', 'integration'], true)) {
        expect($invoke)->toThrow(\yii\web\BadRequestHttpException::class);
        expect($integration->requests)->toBe([]);
    } else {
        $response = $invoke();
        expect(str_contains($response, '"success":true'))->toBe($variant === 'valid');
    }
    $stored = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($stored->status)->toBe($variant === 'valid' ? Payment::STATUS_SUCCESS : Payment::STATUS_PENDING)
        ->and($stored->submissionId)->toBe($submission->id)
        ->and($stored->fieldId)->toBe($field->id)
        ->and($stored->amount)->toBe(25.0)
        ->and($stored->currency)->toBe('USD');
    if ($variant === 'valid') {
        expect($invoke())->toContain('"success":true');
        expect($integration->requests)->toHaveCount(1);
    }
})->with(['valid', 'unsigned', 'expired', 'integration', 'declined', 'reference', '1017']);

it('issues the Opayo challenge token from the persisted payment and completes that challenge once', function (): void {
    [$integration, $submission, $field] = checkoutVerificationFixture('opayo');
    $reference = 'opayo-' . bin2hex(random_bytes(10));
    $integration->payload = ['opayoTokenId' => 'test-card-token', 'opayoSessionKey' => 'test-session'];
    $integration->response = ['status' => '3DAuth', 'transactionId' => $reference, 'acsUrl' => 'https://example.test/challenge', 'cReq' => 'test-request'];
    $decision = \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => $integration->processPayment($submission), ['method' => 'POST']);
    expect($decision->status)->toBe('actionRequired');
    $token = $decision->action['payload']['threeDSSessionData'];
    $identity = \verbb\formie\helpers\PaymentAccess::resolveStatusToken($token);
    expect($identity)->not->toBeNull();
    $stored = Formie::$plugin->getPayments()->getPaymentById($identity['paymentId']);
    expect($stored->reference)->toBe($reference)
        ->and($stored->submissionId)->toBe($submission->id)
        ->and($stored->fieldId)->toBe($field->id)
        ->and($stored->status)->toBe(Payment::STATUS_PENDING);
    $integration->response = ['status' => 'Ok', 'transactionId' => $reference];
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => $integration->processCallback(), [
        'method' => 'POST', 'bodyParams' => ['cres' => 'synthetic-challenge', 'threeDSSessionData' => $token],
    ]);
    $integration->payload = ['opayo3DSComplete' => $reference];
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect(array_column($integration->requests, 1))->toBe(['transactions', 'transactions/' . $reference . '/3d-secure-challenge']);
    expect(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount(1);
});

it('creates Paddle prices in the currency minor unit', function (string $currency, string $expected): void {
    [$integration, $submission, $field] = checkoutVerificationFixture('paddle');
    $field->providerSettings[$integration->handle]['currency'] = $currency;
    expect($integration->processPayment($submission)->status)->toBe('actionRequired');
    expect($integration->requests[1][2]['json']['unit_price'])->toBe(['amount' => $expected, 'currency_code' => $currency]);
})->with([['USD', '2500'], ['JPY', '25']]);


it('does not repeat interrupted Paddle product, price or transaction creation', function (string $resource): void {
    [$integration, $submission] = checkoutVerificationFixture('paddle');
    $integration->loseResponseFor = $resource;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $integration->loseResponseFor = null;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $writes = array_values(array_filter($integration->requests, fn($request) => $request[0] === 'POST'));
    foreach (array_count_values(array_column($writes, 1)) as $count) { expect($count)->toBe(1); }
    $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
    expect($payments)->toHaveCount(1)->and($payments[0]->status)->toBe(Payment::STATUS_PENDING);
})->with(['products', 'prices', 'transactions']);
