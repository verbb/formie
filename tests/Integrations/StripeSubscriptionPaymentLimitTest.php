<?php

declare(strict_types=1);

use verbb\formie\base\Payment;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\References;
use verbb\formie\integrations\payments\Stripe;

it('returns null when no subscription payment limit is configured', function (): void {
    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'stripeTest' => [
                'subscriptionLimitType' => '',
            ],
        ],
    ]);

    $integration->setField($paymentField);

    expect($integration->getSubscriptionPaymentLimit(new Submission()))->toBeNull();
});

it('resolves a fixed subscription payment limit', function (): void {
    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'stripeTest' => [
                'subscriptionLimitType' => Payment::VALUE_TYPE_FIXED,
                'subscriptionLimitFixed' => 3,
            ],
        ],
    ]);

    $integration->setField($paymentField);

    expect($integration->getSubscriptionPaymentLimit(new Submission()))->toBe(3);
});

it('resolves a dynamic subscription payment limit from a submission field', function (): void {
    $form = formie()
        ->form(['title' => 'Stripe Subscription Limit'])
        ->numberField('installments')
        ->create();

    $field = $form->getFieldByHandle('installments');
    $submission = formie()->submission($form)->with([
        'installments' => 6,
    ])->save();

    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'stripeTest' => [
                'subscriptionLimitType' => Payment::VALUE_TYPE_DYNAMIC,
                'subscriptionLimitVariable' => References::field((string)$field->reference),
            ],
        ],
    ]);

    $integration->setField($paymentField);

    expect($integration->getSubscriptionPaymentLimit($submission))->toBe(6);
});

it('returns null for invalid fixed subscription payment limits', function (): void {
    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'stripeTest' => [
                'subscriptionLimitType' => Payment::VALUE_TYPE_FIXED,
                'subscriptionLimitFixed' => 0,
            ],
        ],
    ]);

    $integration->setField($paymentField);

    expect($integration->getSubscriptionPaymentLimit(new Submission()))->toBeNull();
});

it('builds a Stripe subscription schedule payload with iteration limits', function (): void {
    $integration = new Stripe(['name' => 'Stripe', 'handle' => 'stripeTest']);

    $build = new ReflectionMethod(Stripe::class, '_buildSubscriptionSchedulePayload');
    $build->setAccessible(true);

    $payload = $build->invoke($integration, [
        'customer' => 'cus_test',
        'description' => 'Installment plan',
        'metadata' => [
            'submissionId' => 1,
        ],
    ], 'plan_test', 3);

    expect($payload)->toMatchArray([
        'customer' => 'cus_test',
        'start_date' => 'now',
        'end_behavior' => 'cancel',
        'phases' => [
            [
                'items' => [
                    ['plan' => 'plan_test'],
                ],
                'iterations' => 3,
            ],
        ],
        'default_settings' => [
            'collection_method' => 'charge_automatically',
            'description' => 'Installment plan',
        ],
        'metadata' => [
            'submissionId' => 1,
        ],
        'expand' => ['subscription.latest_invoice.payment_intent', 'subscription.pending_setup_intent'],
    ]);
});
