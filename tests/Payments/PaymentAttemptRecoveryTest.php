<?php

use craft\db\Query;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\{DeliveryAttempt, PaymentAttempt, PaymentRecovery, Table};
use verbb\formie\integrations\payments\{Bpoint, Eway, Mollie, Opayo};
use verbb\formie\models\{Payment, PaymentFieldPayload};

trait PaymentAttemptFixture
{
    public array $requests = [];
    public array $payload = [];
    public array $receipt = [];
    public bool $timeout = false;
    public ?Throwable $requestError = null;
    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload { return new PaymentFieldPayload($this->handle, 'payment', $this->payload); }
    public function request(string $method, string $uri, array $options = []): mixed {
        $this->requests[] = [$method, $uri, $options];
        if ($this->timeout) { throw new ConnectException('Response lost', new Request($method, 'https://example.test')); }
        if ($this->requestError) { throw $this->requestError; }
        return $this->receipt;
    }
}

function paymentAttemptFixture(string $provider): array
{
    $integration = match ($provider) {
        'eway' => new class extends Eway { use PaymentAttemptFixture; },
        'bpoint' => new class extends Bpoint { use PaymentAttemptFixture; },
        'opayo' => new class extends Opayo { use PaymentAttemptFixture; },
        'mollie' => new class extends Mollie { use PaymentAttemptFixture; },
    };
    $integration->name = 'Recovery ' . $provider;
    $integration->handle = 'recovery' . $provider . bin2hex(random_bytes(5));
    $integration->payload = match ($provider) {
        'eway' => ['ewayTokenData' => ['cardNumber' => 'encrypted-test-card', 'securityCode' => 'encrypted-test-cvn', 'expiryDate' => '12/30']],
        'bpoint' => ['bpointToken' => 'test-dv-token'],
        'opayo' => ['opayoTokenId' => 'test-card-token', 'opayoSessionKey' => 'test-session-key'],
        default => [],
    };
    $integration->receipt = match ($provider) {
        'eway' => ['TransactionStatus' => true, 'TransactionID' => 'eway-' . uniqid(), 'ResponseCode' => '00'],
        'bpoint' => ['APIResponse' => ['ResponseCode' => 0], 'TxnResp' => ['ResponseCode' => '0', 'BankResponseCode' => '00', 'ReceiptNumber' => 'bpoint-' . uniqid()]],
        'opayo' => ['status' => 'Ok', 'transactionId' => 'opayo-' . uniqid()],
        'mollie' => ['id' => 'tr_' . uniqid(), 'status' => 'open', '_links' => ['checkout' => ['href' => 'https://example.test/checkout']]],
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => get_parent_class($integration),
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    return [$integration, $submission];
}

it('sends one purchase and reuses its persisted receipt on repeated submissions', function (string $provider): void {
    [$integration, $submission] = paymentAttemptFixture($provider);
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $provider): void {
        $expected = $provider === 'mollie' ? 'actionRequired' : 'succeeded';
        $decision = $integration->processPayment($submission);
        test()->assertSame($expected, $decision->status, (string)$decision->message);
        $decision = $integration->processPayment($submission);
        test()->assertSame($expected, $decision->status, (string)$decision->message);
        expect($integration->requests)->toHaveCount(1);
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
        expect($payments)->toHaveCount(1)->and($payments[0]->reference)->not->toBeEmpty();
        $meta = (new Query())->select('meta')->from(Table::FORMIE_SUBMISSION_WORKFLOW)->where(['submissionId' => $submission->id])->column();
        expect(implode('', $meta))->not->toContain('encrypted-test-card')->not->toContain('encrypted-test-cvn')->not->toContain('test-card-token');
    });
})->with(['eway', 'bpoint', 'opayo', 'mollie']);

it('keeps lost responses pending and blocks another charge across retries and changed credentials', function (string $provider): void {
    [$integration, $submission] = paymentAttemptFixture($provider);
    $integration->timeout = true;
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $provider): void {
        expect($integration->processPayment($submission)->status)->toBe('pending');
        $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
        $integration->timeout = false;
        expect($integration->processPayment($submission)->status)->not->toBe('succeeded');
        if ($provider === 'bpoint') { $integration->merchantNumber = 'changed-account'; }
        elseif ($provider === 'opayo') { $integration->integrationKey = 'changed-account'; }
        else { $integration->apiKey = 'changed-account'; }
        expect($integration->processPayment($submission)->status)->not->toBe('succeeded');
        expect($integration->requests)->toHaveCount(1);
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
        expect($payments)->toHaveCount(1)->and($payments[0]->id)->toBe($payment->id)->and($payments[0]->status)->toBe(Payment::STATUS_PENDING);
    });
})->with(['eway', 'bpoint', 'opayo', 'mollie']);

it('allows a new payment only after a confirmed decline', function (string $provider): void {
    [$integration, $submission] = paymentAttemptFixture($provider);
    $success = $integration->receipt;
    $integration->receipt = match ($provider) {
        'eway' => ['TransactionStatus' => false, 'ResponseCode' => '51', 'ResponseMessage' => 'Declined'],
        'bpoint' => ['APIResponse' => ['ResponseCode' => 0], 'TxnResp' => ['ResponseCode' => '1', 'BankResponseCode' => '51', 'ResponseText' => 'Declined']],
        'opayo' => ['status' => 'NotAuthed', 'statusDetail' => 'Declined'],
    };
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $success): void {
        expect($integration->processPayment($submission)->status)->toBe('failed');
        $integration->receipt = $success;
        expect($integration->processPayment($submission)->status)->toBe('succeeded');
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
        expect($payments)->toHaveCount(2)->and($payments[0]->status)->toBe(Payment::STATUS_FAILED)->and($payments[1]->status)->toBe(Payment::STATUS_SUCCESS);
    });
})->with(['eway', 'bpoint', 'opayo']);

it('recovers a successful gateway receipt after the final payment save failed', function (): void {
    [$integration, $submission] = paymentAttemptFixture('eway');
    $original = Formie::$plugin->getPayments();
    $service = new class extends \verbb\formie\services\Payments {
        public bool $failSuccess = true;
        public function savePayment(Payment $payment, bool $runValidation = true): bool {
            if ($this->failSuccess && $payment->status === Payment::STATUS_SUCCESS) { return false; }
            return parent::savePayment($payment, $runValidation);
        }
    };
    Formie::$plugin->set('payments', $service);
    try {
        expect($integration->processPayment($submission)->status)->not->toBe('succeeded');
        $service->failSuccess = false;
        $integration->payload = [];
        expect($integration->processPayment($submission)->status)->toBe('succeeded');
        expect($integration->requests)->toHaveCount(1)->and($service->getSubmissionPayments($submission))->toHaveCount(1);
    } finally { Formie::$plugin->set('payments', $original); }
});

it('records a verified operator outcome and prevents a recovered charge from being sent again', function (): void {
    [$integration, $submission] = paymentAttemptFixture('eway');
    $integration->timeout = true;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $inspection = PaymentRecovery::inspect($payment->id);
    expect($inspection['deliveryState'])->toBe('unknown')->and($inspection['merchantReference'])->toStartWith('fm');
    expect(fn() => PaymentRecovery::resolve($payment->id, 'success', 1, 'USD', 'verified-receipt', 'Verified in test gateway'))->toThrow(RuntimeException::class);
    expect(fn() => PaymentRecovery::resolve($payment->id, 'success', 25, 'USD', '', 'Verified in test gateway'))->toThrow(RuntimeException::class);
    $resolved = PaymentRecovery::resolve($payment->id, 'success', 25, 'USD', 'verified-' . uniqid(), 'Operator test: verified amount and receipt in gateway');
    expect($resolved->status)->toBe('success')->and($resolved->note)->toContain('Operator test');
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect($integration->requests)->toHaveCount(1);
});

it('uses committed payment ownership when a read replica has not received the first attempt', function (): void {
    [$integration, $submission] = paymentAttemptFixture('eway');
    $original = Formie::$plugin->getPayments();
    $db = Craft::$app->getDb();
    $previous = $db->enableSlaves;
    $db->enableSlaves = true;
    $service = new class extends \verbb\formie\services\Payments {
        public function getSubmissionPayments(Submission $submission): array {
            // Simulate a replica that still sees the submission before payment.
            return Craft::$app->getDb()->enableSlaves ? [] : parent::getSubmissionPayments($submission);
        }
    };
    Formie::$plugin->set('payments', $service);
    try {
        expect($integration->processPayment($submission)->status)->toBe('succeeded');
        expect($integration->processPayment($submission)->status)->toBe('succeeded');
        expect($integration->requests)->toHaveCount(1)->and($db->enableSlaves)->toBeTrue();
    } finally {
        $db->enableSlaves = $previous;
        Formie::$plugin->set('payments', $original);
    }
});

it('keeps ambiguous gateway failures unresolved without issuing a second request', function (string $provider): void {
    [$integration, $submission] = paymentAttemptFixture($provider);
    $integration->receipt = match ($provider) {
        'eway' => ['TransactionStatus' => false, 'ResponseCode' => '09', 'ResponseMessage' => 'Request in progress'],
        'bpoint' => ['APIResponse' => ['ResponseCode' => 0], 'TxnResp' => ['ResponseCode' => '1', 'BankResponseCode' => '91']],
        'opayo' => ['status' => 'Error', 'statusDetail' => 'Unknown error'],
    };
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission): void {
        expect($integration->processPayment($submission)->status)->not->toBe('succeeded');
        expect($integration->processPayment($submission)->status)->not->toBe('succeeded');
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
        expect($payments)->toHaveCount(1)->and($payments[0]->status)->toBe(Payment::STATUS_PENDING)->and($integration->requests)->toHaveCount(1);
    });
})->with(['eway', 'bpoint', 'opayo']);

it('permits retry after an operator verifies no charge and rejects duplicate receipt association', function (): void {
    [$integration, $submission] = paymentAttemptFixture('eway');
    $integration->timeout = true;
    $integration->processPayment($submission);
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $other = clone $payment;
    $other->id = null; $other->uid = null; $other->reference = 'already-used-' . uniqid(); $other->status = Payment::STATUS_SUCCESS;
    expect(Formie::$plugin->getPayments()->savePayment($other))->toBeTrue();
    expect(fn() => PaymentRecovery::resolve($payment->id, 'success', 25, 'USD', $other->reference, 'Verified test'))->toThrow(RuntimeException::class, 'another payment');
    Formie::$plugin->getPayments()->deletePayment($other);
    PaymentRecovery::resolve($payment->id, 'failed', 25, 'USD', '', 'Operator test: gateway confirms no charge or open authorisation');
    $integration->timeout = false;
    expect($integration->processPayment($submission)->status)->toBe('succeeded')->and($integration->requests)->toHaveCount(2);
});

it('rejects mismatched Mollie status responses and does not downgrade completed payments', function (): void {
    [$integration, $submission] = paymentAttemptFixture('mollie');
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission): void {
        expect($integration->processPayment($submission)->status)->toBe('actionRequired');
        $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
        $integration->receipt = ['id' => $payment->reference, 'metadata' => ['formiePaymentId' => $payment->id], 'amount' => ['currency' => 'USD', 'value' => '1.00'], 'status' => 'paid'];
        expect(fn() => $integration->getTransaction($payment))->toThrow(Exception::class, 'amount');
        expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->not->toBe(Payment::STATUS_SUCCESS);
        $integration->receipt['amount']['value'] = '25.00';
        $method = new ReflectionMethod(Mollie::class, '_updateFormiePaymentStatus');
        // Capture workflow replay; this fixture verifies gateway reconciliation only.
        $original = Formie::$plugin->getSubmissionProcessor();
        $fake = new class extends \verbb\formie\services\SubmissionProcessor { public function replayPaymentIfSuccessful(Payment $payment): ?\verbb\formie\models\SubmissionExecutionResult { return null; } };
        Formie::$plugin->set('submissionProcessor', $fake);
        try {
            $stale = clone $payment;
            $method->invoke($integration, $payment, $integration->receipt);
            $integration->receipt['status'] = 'open';
            $method->invoke($integration, $stale, $integration->receipt);
            expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_SUCCESS);
        } finally { Formie::$plugin->set('submissionProcessor', $original); }
    });
});

it('recovers an Eway timeout by querying its unique invoice reference without another purchase', function (string $variant): void {
    [$integration, $submission] = paymentAttemptFixture('eway');
    $integration->timeout = true;
    expect($integration->processPayment($submission)->status)->toBe('pending');
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $reference = (new PaymentAttempt($payment))->merchantReference();
    $integration->timeout = false;
    $transaction = ['TransactionID' => 'recovered-' . uniqid(), 'TransactionStatus' => true, 'TransactionCaptured' => true, 'TransactionType' => 1, 'InvoiceReference' => $reference, 'TotalAmount' => 2500, 'CurrencyCode' => '840'];
    if ($variant === 'amount') { $transaction['TotalAmount'] = 100; }
    if ($variant === 'currency') { $transaction['CurrencyCode'] = '036'; }
    if ($variant === 'reference') { $transaction['InvoiceReference'] = 'another-submission'; }
    if ($variant === 'uncaptured') { $transaction['TransactionCaptured'] = false; }
    if ($variant === 'refund') { $transaction['TransactionType'] = 4; }
    $integration->receipt = ['Transactions' => [$transaction]];
    if ($variant === 'duplicate') { $integration->receipt['Transactions'][] = $transaction; }
    if ($variant === 'valid') {
        $integration->getTransaction($payment);
        expect($payment->status)->toBe(Payment::STATUS_SUCCESS);
        expect($integration->processPayment($submission)->status)->toBe('succeeded');
    } else {
        expect(fn() => $integration->getTransaction($payment))->toThrow(Exception::class);
        expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_PENDING);
    }
    expect(array_column($integration->requests, 0))->toBe(['POST', 'GET']);
    expect($integration->requests[1][1])->toBe('Transaction/InvoiceRef/' . $reference);
})->with(['valid', 'amount', 'currency', 'reference', 'uncaptured', 'refund', 'duplicate']);

it('recovers a lost Mollie creation response only with its signed URL and authenticated metadata', function (string $variant): void {
    [$integration, $submission] = paymentAttemptFixture('mollie');
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $variant): void {
        $integration->timeout = true;
        expect($integration->processPayment($submission)->status)->toBe('pending');
        $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
        $integration->timeout = false;
        $id = 'tr_' . bin2hex(random_bytes(6));
        $integration->receipt = ['id' => $id, 'metadata' => ['formiePaymentId' => $payment->id, 'formiePaymentUid' => $payment->uid], 'amount' => ['currency' => 'USD', 'value' => '25.00'], 'status' => 'paid'];
        parse_str(parse_url($integration->requests[0][2]['json']['webhookUrl'], PHP_URL_QUERY), $params);
        if ($variant === 'missing') { unset($params['recoveryToken']); }
        if ($variant === 'tampered') { $params['recoveryToken'] = str_repeat('0', 64); }
        if ($variant === 'wrong-owner') { $params['formiePaymentId'] = (string)($payment->id + 100000); }
        if ($variant === 'metadata') { $integration->receipt['metadata']['formiePaymentUid'] = 'another-payment'; }
        if ($variant === 'amount') { $integration->receipt['amount']['value'] = '1.00'; }
        Craft::$app->getRequest()->setQueryParams($params);
        Craft::$app->getRequest()->setBodyParams(['id' => $id]);
        $original = Formie::$plugin->getSubmissionProcessor();
        $fake = new class extends \verbb\formie\services\SubmissionProcessor { public function replayPaymentIfSuccessful(Payment $payment): ?\verbb\formie\models\SubmissionExecutionResult { return null; } };
        Formie::$plugin->set('submissionProcessor', $fake);
        try {
            expect($integration->processWebhook()->data)->toBe(in_array($variant, ['metadata', 'amount']) ? 'error' : 'success');
            $saved = Formie::$plugin->getPayments()->getPaymentById($payment->id);
            if ($variant === 'valid') {
                expect($saved->status)->toBe(Payment::STATUS_SUCCESS)->and($saved->reference)->toBe($id);
                expect($integration->processPayment($submission)->status)->toBe('succeeded');
                Craft::$app->getRequest()->setQueryParams([]);
                expect($integration->processWebhook()->data)->toBe('success');
                expect(array_column($integration->requests, 0))->toBe(['POST', 'GET', 'GET']);
            } else {
                expect($saved->status)->toBe(Payment::STATUS_PENDING)->and($saved->reference)->toBeNull();
                expect(array_column($integration->requests, 0))->toBe(in_array($variant, ['metadata', 'amount']) ? ['POST', 'GET'] : ['POST']);
            }
        } finally { Formie::$plugin->set('submissionProcessor', $original); }
    });
})->with(['valid', 'missing', 'tampered', 'wrong-owner', 'metadata', 'amount']);

it('distinguishes explicit Mollie API rejections from ambiguous HTTP failures', function (int $status, bool $validBody, bool $rejected): void {
    [$integration, $submission] = paymentAttemptFixture('mollie');
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $status, $validBody, $rejected): void {
        $body = $validBody ? json_encode(['status' => $status, 'detail' => 'No suitable payment methods found']) : '<html>Upstream error</html>';
        $integration->requestError = new \GuzzleHttp\Exception\RequestException('Request failed', new Request('POST', 'https://example.test'), new \GuzzleHttp\Psr7\Response($status, [], $body));
        $decision = $integration->processPayment($submission);
        expect($decision->status)->toBe($rejected ? 'failed' : 'pending');
        if ($rejected) { expect($decision->message)->toContain('Check your Mollie profile payment methods'); }
        $integration->requestError = null;
        expect($integration->processPayment($submission)->status)->toBe($rejected ? 'actionRequired' : 'failed');
        expect($integration->requests)->toHaveCount($rejected ? 2 : 1);
        expect(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount($rejected ? 2 : 1);
    });
})->with([[401, true, true], [422, true, true], [422, false, false], [409, true, false], [503, true, false]]);

it('keeps persisted payment amounts aligned with the currency unit on retry', function (string $provider, string $currency, float $amount, mixed $expected): void {
    [$integration, $submission] = paymentAttemptFixture($provider);
    $field = $integration->getField();
    $field->providerSettings[$integration->handle]['currency'] = $currency;
    $field->providerSettings[$integration->handle]['amountFixed'] = $amount;
    WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $provider, $expected): void {
        $first = $integration->processPayment($submission);
        $second = $integration->processPayment($submission);
        expect($first->status)->toBe($provider === 'mollie' ? 'actionRequired' : 'succeeded')->and($second->status)->toBe($first->status);
        $body = $integration->requests[0][2]['json'];
        $actual = match ($provider) { 'eway' => $body['Payment']['TotalAmount'], 'bpoint' => $body['TxnReq']['Amount'], 'mollie' => $body['amount']['value'] };
        expect($actual)->toBe($expected)->and($integration->requests)->toHaveCount(1);
    });
})->with([
    ['eway', 'USD', 1.234567, 123],
    ['eway', 'JPY', 25.0, 25],
    ['bpoint', 'JPY', 25.0, 25],
    ['mollie', 'JPY', 25.0, '25'],
]);

it('serializes concurrent submissions around one durable purchase', function (): void {
    require_once dirname(__DIR__) . '/Support/ConcurrentPaymentIntegration.php';
    $integration = new \Tests\Support\ConcurrentPaymentIntegration(['name' => 'Concurrent fixture', 'handle' => 'concurrent' . uniqid()]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => \Tests\Support\ConcurrentPaymentIntegration::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'concurrent-payment-');
    $command = [PHP_BINARY, __DIR__ . '/fixtures/concurrent-purchase.php', (string)$integration->id, (string)$submission->id, $path];
    $first = new \Symfony\Component\Process\Process($command);
    $second = new \Symfony\Component\Process\Process($command);
    try {
        $first->start(); $second->start();
        $first->wait(); $second->wait();
        test()->assertSame(0, $first->getExitCode(), $first->getErrorOutput() . $first->getOutput());
        test()->assertSame(0, $second->getExitCode(), $second->getErrorOutput() . $second->getOutput());
        expect(trim($first->getOutput()))->toBe('succeeded')->and(trim($second->getOutput()))->toBe('succeeded');
        expect(file($path))->toHaveCount(1)->and(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount(1);
    } finally { $first->stop(); $second->stop(); @unlink($path); }
});

it('does not resend after a worker exits immediately after the gateway accepts a purchase', function (): void {
    require_once dirname(__DIR__) . '/Support/ConcurrentPaymentIntegration.php';
    $integration = new \Tests\Support\ConcurrentPaymentIntegration(['name' => 'Interrupted fixture', 'handle' => 'interrupted' . uniqid()]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => \Tests\Support\ConcurrentPaymentIntegration::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'interrupted-payment-');
    $command = [PHP_BINARY, __DIR__ . '/fixtures/concurrent-purchase.php', (string)$integration->id, (string)$submission->id, $path];
    try {
        $first = new \Symfony\Component\Process\Process([...$command, 'interrupt']);
        $first->run();
        test()->assertSame(37, $first->getExitCode(), $first->getErrorOutput() . $first->getOutput());
        $retry = new \Symfony\Component\Process\Process($command);
        $retry->run();
        expect(trim($retry->getOutput()))->toBe('pending')->and(file($path))->toHaveCount(1);
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
        expect($payments)->toHaveCount(1)->and($payments[0]->status)->toBe(Payment::STATUS_PENDING);
    } finally { @unlink($path); }
});
