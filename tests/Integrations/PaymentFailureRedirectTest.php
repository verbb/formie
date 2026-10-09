<?php

declare(strict_types=1);

use verbb\formie\controllers\PaymentWebhooksController;
use verbb\formie\controllers\PaymentStatusController;
use verbb\formie\Formie;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\models\Payment as PaymentModel;
use Tests\Support\WebRequestTestHelper;


it('resolves failed payments back to the stored form url', function (): void {
    $integration = new Mollie([
        'name' => 'Failure Redirect Integration ' . uniqid(),
        'handle' => 'failureRedirect' . uniqid(),
        'enabled' => false,
    ]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);

    $form = formie()
        ->form(['title' => 'Failure Redirect Fixture'])
        ->singleLineTextField('fullName')
        ->paymentField('paymentField', [
            'paymentIntegration' => $integration->handle,
            'paymentIntegrationType' => get_class($integration),
        ])
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Payment Fixture'])
        ->save();
    $paymentField = $form->getFieldByHandle('paymentField');

    $payment = new PaymentModel([
        'integrationId' => $integration->id,
        'submissionId' => $submission->id,
        'fieldId' => $paymentField->id,
        'amount' => 10.00,
        'currency' => 'AUD',
        'status' => PaymentModel::STATUS_FAILED,
        'reference' => 'failure-redirect-' . uniqid(),
        'message' => 'Card declined.',
        'redirectUrl' => '/checkout-form',
    ]);
    Formie::$plugin->getPayments()->savePayment($payment, false);

    $statusToken = PaymentAccess::issueStatusToken($payment);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($statusToken): void {
        $request->setQueryParams([
            'statusToken' => $statusToken,
        ]);

        $controller = new PaymentStatusController('formie-payment-failure', Craft::$app);
        $response = $controller->actionPollStatus();

        expect($response->data['status'] ?? null)->toBe('failed')
            ->and($response->data['redirectUrl'] ?? null)->toBe('/checkout-form')
            ->and($response->data['message'] ?? null)->toBe('Card declined.');
    });
});

it('rejects untrusted failed payment redirects and uses the configured form url', function (): void {
    $form = formie()
        ->form(['title' => 'Untrusted Failure Redirect Fixture'])
        ->singleLineTextField('fullName')
        ->create();
    $form->setRedirectUrl('/checkout-form');
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Payment Fixture'])
        ->save();
    $payment = new PaymentModel([
        'status' => PaymentModel::STATUS_FAILED,
        'message' => 'Card declined.',
        'redirectUrl' => 'https://evil.example/login',
    ]);

    expect(Formie::$plugin->getPayments()->resolvePaymentFailureRedirectUrl($payment, $submission, $form))
        ->toBe('/checkout-form');
});

it('allows configured external failed payment redirect origins', function (): void {
    $form = formie()
        ->form(['title' => 'Allowlisted Failure Redirect Fixture'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Payment Fixture'])
        ->save();
    $payment = new PaymentModel([
        'status' => PaymentModel::STATUS_FAILED,
        'redirectUrl' => 'https://checkout.example/return',
    ]);
    $settings = Formie::$plugin->getSettings();
    $original = $settings->completionRedirectAllowedOrigins;
    $settings->completionRedirectAllowedOrigins = ['https://checkout.example'];

    try {
        expect(Formie::$plugin->getPayments()->resolvePaymentFailureRedirectUrl($payment, $submission, $form))
            ->toBe('https://checkout.example/return');
    } finally {
        $settings->completionRedirectAllowedOrigins = $original;
    }
});

it('does not use a polling request referrer as a failed payment redirect', function (): void {
    $form = formie()
        ->form(['title' => 'Failure Redirect Poll Fixture'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Payment Fixture'])
        ->save();
    $payment = new PaymentModel([
        'status' => PaymentModel::STATUS_FAILED,
        'redirectUrl' => 'https://evil.example/login',
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($payment, $submission, $form): void {
        $request->getHeaders()->set('Referer', 'https://craft.example.test/payment-status');

        expect(Formie::$plugin->getPayments()->resolvePaymentFailureRedirectUrl($payment, $submission, $form))
            ->toBe('');
    });
});

it('maps mollie failure statuses to user-facing messages', function (): void {
    $integration = new Mollie([
        'name' => 'Mollie Message Integration ' . uniqid(),
        'handle' => 'mollieMessage' . uniqid(),
    ]);

    $method = new ReflectionMethod(Mollie::class, '_resolveMollieFailureMessage');
    $method->setAccessible(true);

    expect($method->invoke($integration, ['details' => ['failureMessage' => 'Insufficient funds.']], 'failed'))
        ->toBe('Insufficient funds.')
        ->and($method->invoke($integration, [], 'canceled'))
        ->toContain('canceled');
});
