<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Payment;

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
        'submitActionUrl' => References::field((string)$field->reference),
        'submitActionTab' => 'same-tab',
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
        'submitActionUrl' => References::field((string)$field->reference),
        'submitActionTab' => 'same-tab',
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

it('sanitizes stripe callback origin before redirecting', function (string $target): void {
    $integration = new Stripe([
        'name' => 'Security Stripe',
        'handle' => 'securityStripe',
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($integration, $target): void {
        $request->setQueryParams([
            'origin' => $target,
        ]);

        $response = $integration->processCallback();

        expect((string)$response->getHeaders()->get('Location'))
            ->not->toContain('evil.example.test')
            ->not->toContain('javascript:');
    });
})->with([
    'javascript scheme' => ['javascript:alert(1)'],
    'protocol relative' => ['//evil.example.test/path'],
])->group('security');
