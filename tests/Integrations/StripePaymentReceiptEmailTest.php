<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\References;
use verbb\formie\integrations\payments\Stripe;

it('omits stripe receipt_email when the mapped receipt address resolves empty', function (): void {
    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'stripeTest' => [
                'paymentReceipt' => true,
                'paymentReceiptEmail' => '{field:missingEmail}',
            ],
        ],
    ]);

    $integration->setField($paymentField);

    $form = formie()->form(['title' => 'Stripe Receipt Email'])->create();
    $submission = new Submission();
    $submission->setForm($form);

    $payload = [];
    $method = new ReflectionMethod(Stripe::class, '_setPayloadDetails');
    $method->invoke($integration, $payload, $submission, 'single');

    expect($payload)->not->toHaveKey('receipt_email');
});

it('includes stripe receipt_email when the mapped receipt address resolves', function (): void {
    $form = formie()
        ->form(['title' => 'Stripe Receipt Email Resolved'])
        ->emailField('email')
        ->create();

    $emailField = $form->getFieldByHandle('email');
    $submission = formie()->submission($form)->with([
        'email' => 'customer@example.test',
    ])->save();

    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'stripeTest' => [
                'paymentReceipt' => true,
                'paymentReceiptEmail' => References::field((string)$emailField->reference),
            ],
        ],
    ]);

    $integration->setField($paymentField);

    $payload = [];
    $method = new ReflectionMethod(Stripe::class, '_setPayloadDetails');
    $method->invoke($integration, $payload, $submission, 'single');

    expect($payload)->toHaveKey('receipt_email')
        ->and($payload['receipt_email'])->toBe('customer@example.test');
});
