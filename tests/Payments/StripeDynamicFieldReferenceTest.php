<?php

use verbb\formie\Formie;
use verbb\formie\integrations\payments\Stripe;

// Dynamic amount and currency settings store a field reference, which the browser looks up by the field's input key.
function stripeDynamicReferenceField(string $amountType, string $currencyType = 'dynamic'): \verbb\formie\fields\Payment
{
    $integration = new Stripe([
        'name' => 'Stripe dynamic references',
        'handle' => 'stripeDynamic' . bin2hex(random_bytes(6)),
        'publishableKey' => 'pk_test_dynamic',
        'secretKey' => 'sk_test_dynamic',
    ]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = formie()->form(['title' => 'Stripe dynamic references'])
        ->dropdownField('currency', ['options' => [
            ['label' => 'GBP', 'value' => 'GBP', 'isDefault' => true],
            ['label' => 'NOK', 'value' => 'NOK'],
        ]])
        ->numberField('customAmount')
        ->paymentField('payment', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => Stripe::class,
        ])->create();

    $field = $form->getFieldByHandle('payment');
    $field->providerSettings = [$integration->handle => [
        'amountType' => $amountType,
        'amountFixed' => '10.50',
        'amountVariable' => '{field:' . $form->getFieldByHandle('customAmount')->reference . '}',
        'currencyType' => $currencyType,
        'currencyFixed' => 'AUD',
        'currencyVariable' => '{field:' . $form->getFieldByHandle('currency')->reference . '}',
    ]];

    return $field;
}

it('sends a dynamic currency to the browser as the field key with a fixed decimal amount', function() {
    $info = stripeDynamicReferenceField('fixed')->getPaymentIntegration()->getInitialPaymentInformation();

    expect($info)->toBe(['amount' => '10.50', 'currency' => 'currency']);
});

it('sends dynamic amount and currency fields to the browser as field keys', function() {
    $info = stripeDynamicReferenceField('dynamic')->getPaymentIntegration()->getInitialPaymentInformation();

    expect($info)->toBe(['amount' => 'customAmount', 'currency' => 'currency']);
});

it('sends a dynamic amount field to the browser as the field key with a fixed currency', function() {
    $info = stripeDynamicReferenceField('dynamic', 'fixed')->getPaymentIntegration()->getInitialPaymentInformation();

    expect($info)->toBe(['amount' => 'customAmount', 'currency' => 'aud']);
});

it('keeps converting a fixed amount to Stripe units for a fixed currency', function() {
    $info = stripeDynamicReferenceField('fixed', 'fixed')->getPaymentIntegration()->getInitialPaymentInformation();

    expect($info)->toBe(['amount' => 1050, 'currency' => 'aud']);
});
