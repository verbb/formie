<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Payment;
use verbb\formie\elements\Submission;
use Tests\Support\WebRequestTestHelper;
use yii\base\Event;

it('accepts a signed Stripe event once and ignores a tampered event without changing another payment', function (): void {
    $integration = new Stripe(['name' => 'Signed webhook fixture', 'handle' => 'stripe' . bin2hex(random_bytes(8)), 'webhookSecretKey' => 'whsec_local_contract']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->settings(['disableCaptchas' => true])->singleLineTextField('name')
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => Stripe::class,
            'providerSettings' => [$integration->handle => [
                'amountType' => 'fixed', 'amountFixed' => 25,
                'currencyType' => 'fixed', 'currencyFixed' => 'USD',
            ]],
        ])->create();
    $submission = formie()->submission($form)->with(['name' => 'Paid visitor'])->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $otherSubmission = formie()->submission($form)->with(['name' => 'Other visitor'])->save();
    $payments = Formie::$plugin->getPayments();
    $rows = [];
    foreach (['target', 'other'] as $key) {
        $payment = new Payment(['integrationId' => $integration->id, 'submissionId' => $key === 'target' ? $submission->id : $otherSubmission->id, 'fieldId' => $form->getFieldByHandle('payment')->id,
            'amount' => 25, 'currency' => 'USD', 'status' => Payment::STATUS_PENDING, 'reference' => 'pi_' . $key . bin2hex(random_bytes(8))]);
        expect($payments->savePayment($payment))->toBeTrue();
        $rows[$key] = $payment;
    }
    $completed = [];
    $handler = function ($event) use (&$completed): void { $completed[] = $event->submission->id; };
    Event::on(Submission::class, Submission::EVENT_AFTER_COMPLETE, $handler);
    $send = function (string $reference, bool $tampered = false) use ($integration): void {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $reference, $tampered): void {
            $body = json_encode(['id' => 'evt_contract', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => $reference, 'status' => 'succeeded']]], JSON_THROW_ON_ERROR);
            $time = time();
            $_SERVER['HTTP_STRIPE_SIGNATURE'] = 't=' . $time . ',v1=' . hash_hmac('sha256', $time . '.' . $body, 'whsec_local_contract');
            $request->setRawBody($tampered ? $body . ' ' : $body);
            expect($integration->processWebhook()->data)->toBe('ok');
        }, ['method' => 'POST']);
    };
    try {
        $send($rows['target']->reference, true);
        expect($payments->getPaymentById($rows['target']->id)->status)->toBe(Payment::STATUS_PENDING);
        expect($completed)->toBe([]);
        $send($rows['target']->reference);
        expect($payments->getPaymentById($rows['target']->id)->status)->toBe(Payment::STATUS_SUCCESS);
        expect(Submission::find()->id($submission->id)->status(null)->one()->isIncomplete)->toBeFalse();
        $send($rows['target']->reference);
        expect($completed)->toBe([$submission->id]);
        expect($payments->getPaymentById($rows['other']->id)->status)->toBe(Payment::STATUS_PENDING);
        expect((int)(new \craft\db\Query())->from(\verbb\formie\helpers\Table::FORMIE_PAYMENTS)->where(['reference' => $rows['target']->reference])->count())->toBe(1);
    } finally { Event::off(Submission::class, Submission::EVENT_AFTER_COMPLETE, $handler); }
});
