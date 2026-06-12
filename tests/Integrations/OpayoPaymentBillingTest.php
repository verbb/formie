<?php

declare(strict_types=1);

use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\integrations\payments\Opayo;

it('resolves payment billing field keys from static-table value columns', function (): void {
    $integration = new Opayo(['name' => 'Opayo', 'handle' => 'opayoTest']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'opayoTest' => [
                'billingDetails' => [
                    'billingName' => ['value' => '{field:nameRef}'],
                    'billingAddress' => ['value' => '{field:addressRef}.__toString'],
                    'billingEmail' => ['value' => '{field:emailRef}'],
                ],
            ],
        ],
    ]);

    $integration->setField($paymentField);

    $resolve = new ReflectionMethod(Opayo::class, 'getPaymentBillingFieldKey');
    $resolve->setAccessible(true);

    expect($resolve->invoke($integration, 'billingName'))->toBe('{field:nameRef}')
        ->and($resolve->invoke($integration, 'billingAddress'))->toBe('{field:addressRef}')
        ->and($resolve->invoke($integration, 'billingEmail'))->toBe('{field:emailRef}');
});

it('falls back to legacy scalar billing-detail row values', function (): void {
    $integration = new Opayo(['name' => 'Opayo', 'handle' => 'opayoLegacy']);
    $paymentField = new PaymentField([
        'handle' => 'payment',
        'providerSettings' => [
            'opayoLegacy' => [
                'billingDetails' => [
                    'billingEmail' => 'email',
                ],
            ],
        ],
    ]);

    $integration->setField($paymentField);

    $resolve = new ReflectionMethod(Opayo::class, 'getPaymentBillingFieldKey');
    $resolve->setAccessible(true);

    expect($resolve->invoke($integration, 'billingEmail'))->toBe('email');
});
