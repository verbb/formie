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

it('defaults checkout mode to own form', function (): void {
    $integration = new Opayo(['name' => 'Opayo', 'handle' => 'opayo']);

    expect($integration->getCheckoutMode())->toBe(Opayo::CHECKOUT_MODE_OWN_FORM)
        ->and($integration->isDropInCheckoutMode())->toBeFalse();
});

it('supports drop-in checkout mode', function (): void {
    $integration = new Opayo([
        'name' => 'Opayo',
        'handle' => 'opayo',
        'checkoutMode' => Opayo::CHECKOUT_MODE_DROP_IN,
    ]);

    expect($integration->getCheckoutMode())->toBe(Opayo::CHECKOUT_MODE_DROP_IN)
        ->and($integration->isDropInCheckoutMode())->toBeTrue();
});

it('omits own-form card sub-fields in drop-in checkout mode', function (): void {
    $integration = new Opayo([
        'name' => 'Opayo',
        'handle' => 'opayo',
        'checkoutMode' => Opayo::CHECKOUT_MODE_DROP_IN,
    ]);
    $paymentField = new PaymentField(['handle' => 'payment']);

    expect($integration->getPaymentSubFields($paymentField))->toBe([]);
});

it('includes checkout mode in the client module config', function (): void {
    $integration = new Opayo([
        'name' => 'Opayo',
        'handle' => 'opayo',
        'checkoutMode' => Opayo::CHECKOUT_MODE_DROP_IN,
        'vendorName' => 'vendor',
        'integrationKey' => 'key',
        'integrationPassword' => 'password',
    ]);
    $paymentField = new PaymentField(['handle' => 'payment']);
    $integration->setField($paymentField);

    $module = $integration->getClientModule(new \verbb\formie\models\ClientModuleContext([
        'field' => $paymentField,
    ]));

    expect($module)->not->toBeNull()
        ->and($module->config['checkoutMode'] ?? null)->toBe(Opayo::CHECKOUT_MODE_DROP_IN);
});
