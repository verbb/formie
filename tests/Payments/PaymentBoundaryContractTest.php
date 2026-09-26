<?php

use craft\db\Query;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\{PaymentAccess, PaymentCapabilities, PaymentWebhookReceipt, Table};
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\{Payment, PaymentDecision, PaymentMoney, Subscription};
use verbb\formie\enums\{PaymentDecisionStatus, PaymentResumeMode};

it('round trips exact money beyond binary integer precision and enforces currency scale', function (string $decimal, string $currency, string $minor): void {
    $money = PaymentMoney::fromDecimal($decimal, $currency);
    expect($money->minor)->toBe($minor)->and(PaymentMoney::fromMinor($minor, $currency)->decimal())->toBe($decimal);
})->with([['90071992547409.93', 'USD', '9007199254740993'], ['123', 'JPY', '123'], ['1.234', 'KWD', '1234'], ['0.07', 'EUR', '7']]);

it('rejects precision loss and malformed monetary input', function (string $value): void {
    expect(fn() => PaymentMoney::fromDecimal($value, 'USD'))->toThrow(InvalidArgumentException::class);
})->with(['1.234', '1e3', 'NaN', '1.2.3']);

it('serializes typed unknown and cancellation decisions without treating either as success', function (): void {
    expect(PaymentDecision::unknown()->status)->toBe(PaymentDecisionStatus::UNKNOWN)
        ->and(PaymentDecision::unknown()->toArray()['status'])->toBe('unknown')
        ->and(PaymentDecision::cancelled()->status)->toBe(PaymentDecisionStatus::CANCELLED)
        ->and(PaymentDecision::succeeded()->merge(PaymentDecision::unknown())->status)->toBe(PaymentDecisionStatus::UNKNOWN);
});

function paymentBoundaryFixture(): array {
    $integration = new Stripe(['name' => 'Boundary', 'handle' => 'boundary' . bin2hex(random_bytes(6)), 'webhookSecretKey' => 'whsec_contract']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->settings(['disableCaptchas' => true])->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => Stripe::class,
        'providerSettings' => [$integration->handle => ['amountType' => 'fixed', 'amountFixed' => '25.01', 'currencyType' => 'fixed', 'currencyFixed' => 'USD']],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $payment = Formie::$plugin->getPayments()->prepareAttempt($integration, $submission);
    return [$integration, $submission, $payment];
}

it('persists immutable exact snapshots and rejects stale payment writers', function (): void {
    [$integration, $submission, $payment] = paymentBoundaryFixture();
    $payments = Formie::$plugin->getPayments();
    expect($payment->amount)->toBe('25.01')->and($payment->scope['formId'])->toBe($submission->formId);
    expect($payments->prepareAttempt($integration, $submission)->id)->toBe($payment->id);
    $stale = clone $payment;
    $payment->status = Payment::STATUS_SUCCESS;
    $payments->savePayment($payment);
    $stale->status = Payment::STATUS_FAILED;
    expect(fn() => $payments->savePayment($stale))->toThrow(RuntimeException::class, 'changed');
    $current = $payments->getPaymentById($payment->id);
    $current->status = Payment::STATUS_PENDING;
    $payments->savePayment($current);
    expect($payments->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_SUCCESS);
    $current->amount = '25.02';
    expect(fn() => $payments->savePayment($current))->toThrow(RuntimeException::class, 'snapshot');
});

it('separates cancellation and reconciliation authority and supports revocation', function (): void {
    [$integration, $submission, $payment] = paymentBoundaryFixture();
    $status = PaymentAccess::issueStatusToken($payment);
    expect(PaymentAccess::resolveStatusToken($status)['purpose'])->toBe('status')
        ->and(PaymentCapabilities::resolve($status, 'cancel'))->toBeNull()
        ->and(PaymentCapabilities::resolve($status, 'reconcile'))->toBeNull();
    $reconcile = PaymentAccess::issueStatusToken($payment, mode: PaymentResumeMode::RECONCILE);
    expect(PaymentAccess::resolveStatusToken($reconcile)['purpose'])->toBe('reconcile');
    PaymentCapabilities::revoke('reconcile', $payment->id);
    expect(PaymentAccess::resolveStatusToken($reconcile))->toBeNull();
    $row = (new Query())->from(Table::FORMIE_PAYMENT_CAPABILITIES)->where(['purpose' => 'status'])->one();
    expect(json_encode($row))->not->toContain($status);
});

it('keeps recurring charges distinct and retains financial history after archive and hard deletion', function (): void {
    [$integration, $submission, $payment] = paymentBoundaryFixture();
    $payments = Formie::$plugin->getPayments();
    $subscription = $payments->prepareSubscription($integration, $submission);
    expect($subscription->status)->toBe('pending')->and($subscription->planId)->toBeNull();
    $first = $payments->recordRecurring($subscription, 'invoice-first', '25.01', 'USD', Payment::STATUS_SUCCESS, ['status' => 'paid']);
    $same = $payments->recordRecurring($subscription, 'invoice-first', '25.01', 'USD', Payment::STATUS_PENDING, ['status' => 'pending']);
    $second = $payments->recordRecurring($subscription, 'invoice-second', '25.01', 'USD', Payment::STATUS_SUCCESS, ['status' => 'paid']);
    expect($same->id)->toBe($first->id)->and($same->status)->toBe(Payment::STATUS_SUCCESS)->and($second->id)->not->toBe($first->id);
    Formie::$plugin->getSubscriptions()->deleteSubscription($subscription);
    expect(Formie::$plugin->getSubscriptions()->getSubscriptionById($subscription->id)->archivedAt)->not->toBeNull();
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_SUBSCRIPTIONS, ['id' => $subscription->id])->execute();
    expect($payments->getPaymentById($first->id)->subscriptionId)->toBeNull()
        ->and($payments->getPaymentById($first->id)->amount)->toBe('25.01');
});

it('retains exact encrypted webhook evidence with a redacted escaped support projection and deduplicates side effects', function (): void {
    [$integration] = paymentBoundaryFixture();
    $body = "{\n  \"id\": \"evt_<script>\", \"email\": \"private@example.test\", \"secret\": \"sensitive\"\n}";
    $calls = 0;
    $process = function () use (&$calls): void { $calls++; };
    PaymentWebhookReceipt::process($integration, 'test', 'evt_contract', $body, ['signature' => 'private'], $process);
    PaymentWebhookReceipt::process($integration, 'test', 'evt_contract', $body, ['signature' => 'private'], $process);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_contract', 'integrationId' => $integration->id])->one();
    expect($calls)->toBe(1)->and($row['status'])->toBe('processed')->and((int)$row['attempts'])->toBe(1)
        ->and($row['body'])->not->toContain('private@example.test')->and($row['headers'])->not->toContain('private')
        ->and($row['display'])->not->toContain('<script>')->not->toContain('private@example.test')->not->toContain('sensitive')
        ->and(PaymentWebhookReceipt::evidence((int)$row['id']))->toBe($body);
});

it('leaves failed authenticated handling durable and retries it without accepting tampered identity', function (): void {
    [$integration] = paymentBoundaryFixture();
    expect(fn() => PaymentWebhookReceipt::process($integration, 'test', 'evt_retry', '{}', [], fn() => throw new RuntimeException('secret')))->toThrow(RuntimeException::class);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_retry', 'integrationId' => $integration->id])->one();
    expect($row['status'])->toBe('reconciliation')->and($row['error'])->not->toContain('secret');
    PaymentWebhookReceipt::process($integration, 'test', 'evt_retry', '{}', [], fn() => false);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_retry', 'integrationId' => $integration->id])->one();
    expect($row['status'])->toBe('ignored')->and((int)$row['attempts'])->toBe(2);
    expect(fn() => PaymentWebhookReceipt::process($integration, 'test', 'evt_retry', '{ }', [], fn() => true))->toThrow(RuntimeException::class, 'different evidence');
});

it('deduplicates the same GoCardless event redelivered in a different signed batch', function (): void {
    [$integration] = paymentBoundaryFixture();
    $first = '{"events":[{"id":"EV1"}]}';
    $second = '{"events":[{"id":"EV2"},{"id":"EV1"}]}';
    $calls = 0;
    $handler = function () use (&$calls) { $calls++; };
    PaymentWebhookReceipt::process($integration, 'test', 'EV1', $first, [], $handler, '{"id":"EV1"}');
    PaymentWebhookReceipt::process($integration, 'test', 'EV1', $second, [], $handler, '{"id":"EV1"}');
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['integrationId' => $integration->id, 'eventId' => 'EV1'])->one();
    expect($calls)->toBe(1)->and(PaymentWebhookReceipt::evidence((int)$row['id']))->toBe($first);
});

it('prevents public display of decrypted webhook evidence', function (): void {
    [$integration] = paymentBoundaryFixture();
    PaymentWebhookReceipt::process($integration, 'test', 'secret', '{"customer":"private"}', [], fn() => true);
    $id = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->select('id')->where(['integrationId' => $integration->id])->scalar();
    WebRequestTestHelper::withWebRequestContext(function () use ($id) {
        expect(fn() => PaymentWebhookReceipt::evidence((int)$id))->toThrow(\yii\web\ForbiddenHttpException::class);
    });
});

it('keeps a browser return and a read capability inert even with forged success and gateway flags', function (): void {
    [$integration, $submission, $payment] = paymentBoundaryFixture();
    $token = PaymentAccess::issueStatusToken($payment);
    WebRequestTestHelper::withWebRequestContext(function () use ($payment, $token) {
        $return = new \verbb\formie\controllers\PaymentReturnController('payment-return', Craft::$app);
        expect($return->actionIndex()->statusCode)->toBe(302);
        $status = new \verbb\formie\controllers\PaymentStatusController('payment-status', Craft::$app);
        expect($status->actionPollStatus()->data['status'])->toBe('pending');
        expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->version)->toBe($payment->version);
    }, ['queryParams' => ['statusToken' => $token, 'checkGateway' => '1', 'status' => 'success', 'payment_intent' => 'pi_forged']]);
});

class AtomicBoundaryPayment extends \verbb\formie\base\Payment
{
    public static int $charges = 0;
    public static function displayName(): string { return 'Atomic boundary'; }
    public function hasValidSettings(): bool { return true; }
    protected function executePayment(\verbb\formie\elements\Submission $submission): PaymentDecision {
        expect(Craft::$app->getDb()->getTransaction()?->getIsActive() ?? false)->toBeFalse();
        $stored = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
        expect($stored->isIncomplete)->toBeTrue();
        $payment = Formie::$plugin->getPayments()->prepareAttempt($this, $submission);
        expect($payment->id)->not->toBeNull();
        self::$charges++;
        $payment->reference = 'atomic-' . $payment->uid;
        $payment->status = Payment::STATUS_SUCCESS;
        Formie::$plugin->getPayments()->savePayment($payment);
        return PaymentDecision::succeeded($this->handle, $payment->reference);
    }
}

it('rolls back the second payment and submission commit while retaining provider evidence for charge-free recovery', function (string $failure): void {
    $integration = new AtomicBoundaryPayment(['name' => 'Atomic', 'handle' => 'atomic' . uniqid()]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->settings(['disableCaptchas' => true])->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => AtomicBoundaryPayment::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => '25.01']],
    ])->create();
    $submission = new \verbb\formie\elements\Submission();
    $fail = function ($event) use ($failure) {
        if ($failure === 'submission' && $event->element instanceof \verbb\formie\elements\Submission && !$event->element->isIncomplete) {
            throw new RuntimeException('Synthetic second-commit failure');
        }
    };
    $original = Formie::$plugin->getPayments();
    if ($failure === 'payment') {
        Formie::$plugin->set('payments', new class extends \verbb\formie\services\Payments {
            public function commitTransition(Payment $payment): bool { return false; }
        });
    }
    AtomicBoundaryPayment::$charges = 0;
    \yii\base\Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_BEFORE_SAVE_ELEMENT, $fail);
    try {
        try { runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission])); } catch (RuntimeException) {}
    } finally {
        \yii\base\Event::off(\craft\services\Elements::class, \craft\services\Elements::EVENT_BEFORE_SAVE_ELEMENT, $fail);
        Formie::$plugin->set('payments', $original);
    }
    expect(AtomicBoundaryPayment::$charges)->toBe(1);
    $stored = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    expect($stored->isIncomplete)->toBeTrue();
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($stored)[0];
    expect($payment->status)->toBe(Payment::STATUS_PROCESSING)
        ->and($payment->scope['providerOutcome']['status'])->toBe(Payment::STATUS_SUCCESS)
        ->and($payment->scope['submissionTransition'] ?? null)->toBeNull();
    $result = Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);
    expect($result->response->success)->toBeTrue();
    $saved = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($saved->status)->toBe(Payment::STATUS_SUCCESS)->and($saved->scope['submissionTransition']['complete'])->toBeTrue();
    expect(AtomicBoundaryPayment::$charges)->toBe(1);
    expect(Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($saved))->toBeNull();
})->with(['submission', 'payment']);
