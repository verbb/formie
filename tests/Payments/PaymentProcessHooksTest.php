<?php

use verbb\formie\Formie;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\{Payment, PaymentDecision};
use yii\base\Event;

class HookContractPayment extends Stripe
{
    public static int $calls = 0;
    public static string $outcome = 'succeeded';
    protected function executePayment(Submission $submission): PaymentDecision
    {
        self::$calls++;
        return match (self::$outcome) {
            'failed' => PaymentDecision::failed('Declined', $this->handle),
            'unknown' => PaymentDecision::unknown('Response unavailable', $this->handle),
            default => PaymentDecision::succeeded($this->handle, 'paid-reference'),
        };
    }
}

function hookContractFixture(): array
{
    HookContractPayment::$calls = 0;
    $integration = new HookContractPayment(['name' => 'Hook contract', 'handle' => 'hookContract' . bin2hex(random_bytes(5)), 'secretKey' => 'fixture']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => HookContractPayment::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => '25']],
    ])->settings(['disableCaptchas' => true])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    return [$integration, $submission];
}

it('cancels before creating a payment or subscription intent', function (): void {
    [$integration, $submission] = hookContractFixture();
    $before = 0;
    $integration->on(PaymentIntegration::EVENT_BEFORE_PROCESS_PAYMENT, function ($event) use (&$before): void { $before++; $event->isValid = false; });
    expect($integration->processPayment($submission)->status)->toBe(PaymentDecision::STATUS_NOT_REQUIRED)
        ->and($before)->toBe(1)->and(HookContractPayment::$calls)->toBe(0)
        ->and(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toBe([]);
});

it('keeps provider certainty when an after hook rejects local completion', function (string $outcome, string $paymentStatus, $decisionStatus): void {
    [$integration, $submission] = hookContractFixture();
    HookContractPayment::$outcome = $outcome;
    $after = 0;
    $integration->on(PaymentIntegration::EVENT_AFTER_PROCESS_PAYMENT, function ($event) use (&$after): void { $after++; $event->isValid = false; });
    expect($integration->processPayment($submission)->status)->toBe($decisionStatus)->and($after)->toBe(1);
    $payments = Formie::$plugin->getPayments()->getSubmissionPayments($submission);
    expect($payments)->toHaveCount(1)->and($payments[0]->status)->toBe($paymentStatus);
    if ($outcome === 'succeeded') {
        expect($integration->processPayment($submission)->status)->toBe(PaymentDecision::STATUS_FAILED)
            ->and(HookContractPayment::$calls)->toBe(1)->and($after)->toBe(2);
    }
})->with([
    ['succeeded', Payment::STATUS_SUCCEEDED, PaymentDecision::STATUS_FAILED],
    ['failed', Payment::STATUS_FAILED, PaymentDecision::STATUS_FAILED],
    ['unknown', Payment::STATUS_UNKNOWN, PaymentDecision::STATUS_UNKNOWN],
]);

it('honours the after hook during completion replay without undoing a successful charge', function (): void {
    [$integration, $submission] = hookContractFixture();
    $submission->isIncomplete = true;
    Craft::$app->getElements()->saveElement($submission, false);
    HookContractPayment::$outcome = 'succeeded';
    expect($integration->processPayment($submission)->status)->toBe(PaymentDecision::STATUS_SUCCEEDED);
    $payment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    $handler = function ($event): void { $event->isValid = false; };
    Event::on(HookContractPayment::class, PaymentIntegration::EVENT_AFTER_PROCESS_PAYMENT, $handler);
    try {
        $result = Formie::$plugin->getSubmissionRequests()->executePaymentReplay($payment);
        expect($result->response->outcome->type->value)->toBe('paymentFailed');
        $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
        expect($saved->isIncomplete)->toBeTrue()
            ->and(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe(Payment::STATUS_SUCCEEDED)
            ->and(HookContractPayment::$calls)->toBe(1);
    } finally { Event::off(HookContractPayment::class, PaymentIntegration::EVENT_AFTER_PROCESS_PAYMENT, $handler); }
});
