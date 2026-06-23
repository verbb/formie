<?php

declare(strict_types=1);

use verbb\formie\controllers\PaymentWebhooksController;
use verbb\formie\Formie;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\models\Payment as PaymentModel;
use Tests\Support\WebRequestTestHelper;

use Craft;

it('resolves failed external payment redirects back to the stored form url', function (): void {
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
        'redirectUrl' => 'https://example.test/checkout-form',
    ]);
    Formie::$plugin->getPayments()->savePayment($payment, false);

    $statusToken = PaymentAccess::issueStatusToken($payment);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($statusToken): void {
        $request->setQueryParams([
            'statusToken' => $statusToken,
            'paymentUid' => (string)$payment->uid,
        ]);

        $controller = new PaymentWebhooksController('formie-payment-failure', Craft::$app);
        $response = $controller->actionPollStatus();

        expect($response->data['status'] ?? null)->toBe('failed')
            ->and($response->data['redirectUrl'] ?? null)->toBe('https://example.test/checkout-form')
            ->and($response->data['message'] ?? null)->toBe('Card declined.');
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
