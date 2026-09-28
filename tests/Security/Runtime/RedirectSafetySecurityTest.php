<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Payment;

it('preserves legitimate redirects while rejecting obfuscated active schemes', function (): void {
    expect(StringHelper::sanitizeRedirectUrl('/thanks?submission=42'))->toBe('/thanks?submission=42')
        ->and(StringHelper::sanitizeRedirectUrl('https://payments.example.test/complete'))->toBe('https://payments.example.test/complete')
        ->and(StringHelper::sanitizeRedirectUrl("java\nscript:alert(1)"))->toBe('')
        ->and(StringHelper::sanitizeRedirectUrl('java&#x73;cript:alert(1)'))->toBe('')
        ->and(StringHelper::sanitizeRedirectUrl('\\\\evil.example.test/path'))->toBe('');
})->group('security');

it('rejects javascript redirect urls resolved from submission references', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Scheme Security'])
        ->singleLineTextField('redirectTarget')
        ->create();

    $field = $form->getFieldByHandle('redirectTarget');
    $submission = formie()
        ->submission($form)
        ->with(['redirectTarget' => 'javascript:alert(1)'])
        ->save();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'redirectUrl' => References::field((string)$field->reference),
        'redirectTarget' => 'same-tab',
    ], false);
    $form->setCurrentSubmission($submission);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue()
        ->and($form->getRedirectUrl())->toBe('');
})->group('security');

it('rejects protocol-relative redirect urls resolved from submission references', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Protocol Relative Security'])
        ->singleLineTextField('redirectTarget')
        ->create();

    $field = $form->getFieldByHandle('redirectTarget');
    $submission = formie()
        ->submission($form)
        ->with(['redirectTarget' => '//evil.example.test/path'])
        ->save();

    $form->settings->setAttributes([
        'submitAction' => 'url',
        'redirectUrl' => References::field((string)$field->reference),
        'redirectTarget' => 'same-tab',
    ], false);
    $form->setCurrentSubmission($submission);

    expect(\Craft::$app->getElements()->saveElement($form))->toBeTrue()
        ->and($form->getRedirectUrl())->toBe('');
})->group('security');

it('applies redirect scheme safety to payment success redirect urls', function (string $target): void {
    $form = formie()
        ->form(['title' => 'Payment Redirect Scheme Security'])
        ->singleLineTextField('redirectTarget')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['redirectTarget' => $target])
        ->save();
    $payment = new Payment([
        'submissionId' => (int)$submission->id,
    ]);

    $url = Formie::$plugin->getPayments()->resolvePaymentSuccessRedirectUrl(
        $payment,
        $submission,
        $form,
        $target
    );

    expect($url)->toBe('');
})->with([
    'javascript scheme' => ['javascript:alert(1)'],
    'protocol relative' => ['//evil.example.test/path'],
])->group('security');

it('ignores untrusted origins on the provider return endpoint', function (string $target): void {
    $integration = new Stripe([
        'name' => 'Security Stripe',
        'handle' => 'securityStripe',
    ]);

    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $payment = new \verbb\formie\models\Payment(['integrationId' => $integration->id, 'amount' => '1.00', 'currency' => 'USD', 'status' => 'pending']);
    Formie::$plugin->getPayments()->savePayment($payment);
    $token = \verbb\formie\helpers\PaymentAccess::issueReturnToken($payment);
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $target, $token): void {
        $request->setQueryParams([
            'origin' => $target, 'returnToken' => $token,
        ]);

        $response = (new \verbb\formie\controllers\PaymentReturnController('payment-return', Craft::$app))->actionIndex();

        expect((string)$response->getHeaders()->get('Location'))
            ->not->toContain('evil.example.test')
            ->not->toContain('javascript:');
    });
})->with([
    'javascript scheme' => ['javascript:alert(1)'],
    'protocol relative' => ['//evil.example.test/path'],
])->group('security');
