<?php

use craft\db\Query;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\enums\PaymentCapabilityPurpose;
use verbb\formie\helpers\{PaymentAccess, PaymentCapabilities, Table};
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\{Payment, PaymentDecision, PaymentMoney, Subscription};
use verbb\formie\models\payments\{PaymentWebhookCommand, PaymentWebhookReceipt, VerifiedWebhook, VerifiedWebhookBatch};
use verbb\formie\enums\PaymentDecisionStatus;

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
        ->and(PaymentDecision::succeeded()->merge(PaymentDecision::unknown())->status)->toBe(PaymentDecisionStatus::UNKNOWN)
        ->and((new ReflectionClass(\verbb\formie\models\PaymentAction::class))->getConstructor()->isPrivate())->toBeTrue();
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

it('separates return, status and cancellation authority and supports revocation', function (): void {
    [$integration, $submission, $payment] = paymentBoundaryFixture();
    $status = PaymentAccess::issueStatusToken($payment);
    $return = PaymentAccess::issueReturnToken($payment);
    expect(PaymentAccess::resolveStatusToken($status)['purpose'])->toBe(PaymentCapabilityPurpose::STATUS->value)
        ->and(PaymentCapabilities::resolve($status, PaymentCapabilityPurpose::CANCEL))->toBeNull()
        ->and(PaymentAccess::resolveReturnToken($status))->toBeNull()
        ->and(PaymentAccess::resolveStatusToken($return))->toBeNull()
        ->and(PaymentAccess::resolveReturnToken($return)['purpose'])->toBe(PaymentCapabilityPurpose::RETURN->value);
    PaymentCapabilities::revoke(PaymentCapabilityPurpose::RETURN, $payment->id);
    expect(PaymentAccess::resolveReturnToken($return))->toBeNull();
    $row = (new Query())->from(Table::FORMIE_PAYMENT_CAPABILITIES)->where(['purpose' => PaymentCapabilityPurpose::STATUS->value])->one();
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

class BoundaryWebhookPayment extends Stripe
{
    public static int $calls = 0;
    public static bool $fail = false;

    public function verifyWebhook(PaymentWebhookCommand $request): VerifiedWebhookBatch
    {
        $payload = json_decode($request->body, true, 512, JSON_THROW_ON_ERROR);
        $fingerprint = $payload;
        ksort($fingerprint);

        return new VerifiedWebhookBatch(
            $this->getWebhookAccountFingerprint('test'),
            'test',
            [new VerifiedWebhook((string)$payload['id'], (string)($payload['type'] ?? 'test'), 'test', null, null, $payload, json_encode($fingerprint))],
            ['signature' => 'private'],
        );
    }

    public function handleWebhook(PaymentWebhookReceipt $receipt): void
    {
        self::$calls++;
        if (self::$fail) {
            throw new RuntimeException('secret');
        }
    }
}

class LegacyBoundaryWebhookPayment extends \verbb\formie\base\Payment
{
    public static int $calls = 0;

    public static function displayName(): string
    {
        return 'Legacy boundary';
    }

    public function hasValidSettings(): bool
    {
        return true;
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function processWebhook(): \yii\web\Response
    {
        self::$calls++;
        $response = new \craft\web\Response();
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->data = 'legacy';

        return $response;
    }
}

class CadencedBoundaryPayment extends \verbb\formie\base\Payment
{
    public static int $lookups = 0;

    public static function displayName(): string
    {
        return 'Cadenced boundary';
    }

    public function hasValidSettings(): bool
    {
        return true;
    }

    public function getTransaction(Payment $payment): void
    {
        self::$lookups++;
    }
}

function boundaryWebhookFixture(): BoundaryWebhookPayment {
    $integration = new BoundaryWebhookPayment(['name' => 'Webhook', 'handle' => 'webhook' . bin2hex(random_bytes(6))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    BoundaryWebhookPayment::$calls = 0;
    BoundaryWebhookPayment::$fail = false;

    return $integration;
}

it('isolates Formie 3 webhook adapters behind the compatibility facade', function (): void {
    LegacyBoundaryWebhookPayment::$calls = 0;
    $integration = new LegacyBoundaryWebhookPayment(['name' => 'Legacy', 'handle' => 'legacy' . uniqid()]);

    expect($integration->processWebhooks()->data)->toBe('legacy')
        ->and(LegacyBoundaryWebhookPayment::$calls)->toBe(1);
});

it('uses a stable webhook URL as the canonical payment endpoint name', function (): void {
    $integration = boundaryWebhookFixture();

    expect($integration->getWebhookUrl())->toContain('integrationUid=' . $integration->uid)
        ->and($integration->getRedirectUri())->toBe($integration->getWebhookUrl());
});

it('separates webhook identities when provider credentials change', function (): void {
    $integration = new Stripe([
        'secretKey' => 'sk_test_first-account',
        'webhookSecretKey' => 'whsec_account-boundary',
    ]);
    $body = json_encode([
        'id' => 'evt_account_boundary',
        'type' => 'payment_intent.processing',
        'livemode' => false,
        'data' => ['object' => ['id' => 'pi_account_boundary', 'object' => 'payment_intent']],
    ], JSON_THROW_ON_ERROR);
    $time = time();
    $signature = 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $body, 'whsec_account-boundary');
    $command = new PaymentWebhookCommand(0, $body, ['Stripe-Signature' => $signature]);
    $first = $integration->verifyWebhook($command);
    $integration->secretKey = 'sk_test_second-account';
    $second = $integration->verifyWebhook($command);

    expect($first->accountFingerprint)->not->toBe($second->accountFingerprint);
});

it('keeps browser status reads on the persisted reconciliation cadence', function (): void {
    $integration = new CadencedBoundaryPayment(['name' => 'Cadenced', 'handle' => 'cadenced' . uniqid()]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $payment = new Payment([
        'integrationId' => $integration->id,
        'amount' => '10.00',
        'currency' => 'USD',
        'status' => Payment::STATUS_PENDING,
    ]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    CadencedBoundaryPayment::$lookups = 0;

    $payment = Formie::$plugin->getPayments()->refreshIfDue($payment);
    expect(CadencedBoundaryPayment::$lookups)->toBe(1)
        ->and($payment->nextReconcileAt)->toBeGreaterThan(time());

    $statusToken = PaymentAccess::issueStatusToken($payment);
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $controller = new \verbb\formie\controllers\PaymentStatusController('payment-status', Craft::$app);
        expect($controller->actionPollStatus()->data['status'])->toBe('pending');
    }, ['queryParams' => ['statusToken' => $statusToken, 'checkGateway' => '1']]);

    expect(CadencedBoundaryPayment::$lookups)->toBe(1);
});

it('retains exact encrypted webhook evidence with a redacted escaped support projection and deduplicates side effects', function (): void {
    $integration = boundaryWebhookFixture();
    $body = "{\n  \"id\": \"evt_<script>\", \"type\": \"contract\", \"email\": \"private@example.test\", \"secret\": \"sensitive\"\n}";
    $command = new PaymentWebhookCommand($integration->id, $body, []);
    Formie::$plugin->getPaymentWebhooks()->receive($integration, $command, true);
    Formie::$plugin->getPaymentWebhooks()->receive($integration, $command, true);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_<script>', 'integrationId' => $integration->id])->one();
    expect(BoundaryWebhookPayment::$calls)->toBe(1)->and($row['status'])->toBe('processed')->and((int)$row['attempts'])->toBe(1)
        ->and($row['body'])->not->toContain('private@example.test')->and($row['headers'])->not->toContain('private')
        ->and($row['display'])->not->toContain('<script>')->not->toContain('private@example.test')->not->toContain('sensitive')
        ->and(Formie::$plugin->getPaymentWebhooks()->evidence((int)$row['id'])['body'])->toBe($body);
});

it('fires the typed after-process event once after durable domain handling', function (): void {
    $integration = boundaryWebhookFixture();
    $events = [];
    $handler = function ($event) use (&$events): void {
        $events[] = $event;
    };
    \yii\base\Event::on(BoundaryWebhookPayment::class, \verbb\formie\base\Payment::EVENT_AFTER_PROCESS_WEBHOOK, $handler);

    try {
        Formie::$plugin->getPaymentWebhooks()->receive(
            $integration,
            new PaymentWebhookCommand($integration->id, '{"id":"evt_lifecycle"}', []),
            true,
        );
    } finally {
        \yii\base\Event::off(BoundaryWebhookPayment::class, \verbb\formie\base\Payment::EVENT_AFTER_PROCESS_WEBHOOK, $handler);
    }

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(\verbb\formie\events\PaymentWebhookLifecycleEvent::class)
        ->and($events[0]->receipt->providerEventId)->toBe('evt_lifecycle');
});

it('leaves failed authenticated handling durable and retries it without accepting tampered identity', function (): void {
    $integration = boundaryWebhookFixture();
    BoundaryWebhookPayment::$fail = true;
    expect(fn() => Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, '{"id":"evt_retry"}', []), true))->toThrow(RuntimeException::class);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_retry', 'integrationId' => $integration->id])->one();
    expect($row['status'])->toBe('scheduled')->and($row['nextAttemptAt'])->not->toBeNull()->and($row['error'])->not->toContain('secret');
    BoundaryWebhookPayment::$fail = false;
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_WEBHOOK_RECEIPTS, [
        'nextAttemptAt' => gmdate('Y-m-d H:i:s', time() - 1),
    ], ['id' => $row['id']])->execute();
    Formie::$plugin->getPaymentWebhooks()->process((int)$row['id']);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_retry', 'integrationId' => $integration->id])->one();
    expect($row['status'])->toBe('processed')->and((int)$row['attempts'])->toBe(2);
    expect(fn() => Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, '{"id":"evt_retry","changed":true}', []), true))->toThrow(RuntimeException::class, 'different evidence');
});

it('does not enqueue duplicate work while a webhook receipt is actively processing', function (): void {
    $integration = boundaryWebhookFixture();
    Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, '{"id":"evt_active"}', []));
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_active', 'integrationId' => $integration->id])->one();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_WEBHOOK_RECEIPTS, [
        'status' => 'processing',
        'startedAt' => gmdate('Y-m-d H:i:s'),
    ], ['id' => $row['id']])->execute();

    expect(Formie::$plugin->getPaymentWebhooks()->schedule((int)$row['id']))->toBeFalse();
});

it('counts an unavailable integration as a bounded webhook processing attempt', function (): void {
    $integration = boundaryWebhookFixture();
    Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, '{"id":"evt_missing_integration"}', []));
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['eventId' => 'evt_missing_integration', 'integrationId' => $integration->id])->one();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_WEBHOOK_RECEIPTS, [
        'integrationUid' => '00000000-0000-4000-8000-000000000000',
        'status' => 'verified',
        'scheduledAt' => null,
    ], ['id' => $row['id']])->execute();

    expect(fn() => Formie::$plugin->getPaymentWebhooks()->process((int)$row['id']))->toThrow(RuntimeException::class, 'unavailable');
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['id' => $row['id']])->one();
    expect((int)$row['attempts'])->toBe(1)->and($row['status'])->toBe('scheduled');
});

it('deduplicates the same GoCardless event redelivered in a different signed batch', function (): void {
    $integration = boundaryWebhookFixture();
    $first = '{"id":"EV1","type":"batch"}';
    $second = "{\n\"type\":\"batch\",\"id\":\"EV1\"\n}";
    Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, $first, []), true);
    Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, $second, []), true);
    $row = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['integrationId' => $integration->id, 'eventId' => 'EV1'])->one();
    expect(BoundaryWebhookPayment::$calls)->toBe(1)->and(Formie::$plugin->getPaymentWebhooks()->evidence((int)$row['id'])['body'])->toBe($first);
});

it('prevents public display of decrypted webhook evidence', function (): void {
    $integration = boundaryWebhookFixture();
    Formie::$plugin->getPaymentWebhooks()->receive($integration, new PaymentWebhookCommand($integration->id, '{"id":"secret","customer":"private"}', []), true);
    $id = (new Query())->from(Table::FORMIE_WEBHOOK_RECEIPTS)->select('id')->where(['integrationId' => $integration->id])->scalar();
    WebRequestTestHelper::withWebRequestContext(function () use ($id) {
        expect(fn() => Formie::$plugin->getPaymentWebhooks()->evidence((int)$id))->toThrow(\yii\web\ForbiddenHttpException::class);
    });
});

it('keeps a browser return and a read capability inert even with forged success and gateway flags', function (): void {
    [$integration, $submission, $payment] = paymentBoundaryFixture();
    $returnToken = PaymentAccess::issueReturnToken($payment);
    WebRequestTestHelper::withWebRequestContext(function () use ($returnToken) {
        $return = new \verbb\formie\controllers\PaymentReturnController('payment-return', Craft::$app);
        expect($return->actionIndex()->statusCode)->toBe(302);
    }, ['queryParams' => ['returnToken' => $returnToken, 'status' => 'success', 'payment_intent' => 'pi_forged']]);
    $statusToken = PaymentAccess::issueStatusToken($payment);
    WebRequestTestHelper::withWebRequestContext(function () use ($payment) {
        $status = new \verbb\formie\controllers\PaymentStatusController('payment-status', Craft::$app);
        expect($status->actionPollStatus()->data['status'])->toBe('pending');
        expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->version)->toBe($payment->version);
    }, ['queryParams' => ['statusToken' => $statusToken, 'checkGateway' => '1', 'status' => 'success', 'payment_intent' => 'pi_forged']]);
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
    $result = Formie::$plugin->getSubmissionRequests()->replayPaymentIfSuccessful($payment);
    expect($result->response->success)->toBeTrue();
    $saved = Formie::$plugin->getPayments()->getPaymentById($payment->id);
    expect($saved->status)->toBe(Payment::STATUS_SUCCESS)->and($saved->scope['submissionTransition']['complete'])->toBeTrue();
    expect(AtomicBoundaryPayment::$charges)->toBe(1);
    expect(Formie::$plugin->getSubmissionRequests()->replayPaymentIfSuccessful($saved))->toBeNull();
})->with(['submission', 'payment']);
