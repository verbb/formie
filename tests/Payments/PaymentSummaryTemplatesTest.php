<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\integrations\payments\GoCardless;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\integrations\payments\Moneris;
use verbb\formie\integrations\payments\Paddle;
use verbb\formie\integrations\payments\Square;
use verbb\formie\models\Payment;
use verbb\formie\models\Subscription;

function paymentSummaryTemplateFixture(string $class): array
{
    $provider = new $class([
        'name' => 'Summary template',
        'handle' => 'summaryTemplate' . bin2hex(random_bytes(6)),
    ]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($provider, false))->toBeTrue();

    $config = ['paymentIntegration' => $provider->handle, 'paymentIntegrationType' => $class];
    $form = formie()->form(['title' => 'Payment summary template'])
        ->paymentField('firstPayment', $config)
        ->paymentField('secondPayment', $config)
        ->create();
    $submission = formie()->submission($form)->save();

    return [$provider, $form, $submission];
}

it('renders saved payment summaries for each field through the submission API', function(string $class) {
    [$provider, $form, $submission] = paymentSummaryTemplateFixture($class);

    expect($submission->getPaymentSummaryHtml())->toBeNull();

    foreach ([['firstPayment', 12.34, Payment::STATUS_SUCCEEDED], ['secondPayment', 98.76, Payment::STATUS_FAILED]] as [$handle, $amount, $status]) {
        $payment = new Payment([
            'integrationId' => $provider->id,
            'submissionId' => $submission->id,
            'fieldId' => $form->getFieldByHandle($handle)->id,
            'amount' => $amount,
            'currency' => 'AUD',
            'status' => $status,
            'reference' => 'local-' . bin2hex(random_bytes(6)),
        ]);
        expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    }

    $first = (string)$provider->getSubmissionSummaryHtml($submission, $form->getFieldByHandle('firstPayment'));
    $second = (string)$provider->getSubmissionSummaryHtml($submission, $form->getFieldByHandle('secondPayment'));
    $summary = (string)$submission->getPaymentSummaryHtml();

    expect($first)->toContain($provider::displayName())->toContain('12.34')->toContain('Succeeded')->not->toContain('98.76')
        ->and($second)->toContain('98.76')->toContain('Failed')->not->toContain('12.34')
        ->and($summary)->toContain('12.34')->toContain('98.76');
})->with([GoCardless::class, Moneris::class, Paddle::class, Square::class, Mollie::class]);

it('renders GoCardless subscriptions through the active template with cancellation permission enforced', function() {
    [$provider, $form, $submission] = paymentSummaryTemplateFixture(GoCardless::class);
    $field = $form->getFieldByHandle('firstPayment');
    $subscription = new Subscription([
        'integrationId' => $provider->id,
        'submissionId' => $submission->id,
        'fieldId' => $field->id,
        'reference' => 'local-subscription-' . bin2hex(random_bytes(6)),
        'status' => SubscriptionStatus::ACTIVE,
    ]);
    expect(Formie::$plugin->getSubscriptions()->saveSubscription($subscription, false))->toBeTrue();

    $originalIdentity = Craft::$app->getUser()->getIdentity();

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($provider, $submission, $field) {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(null);

            $readOnly = (string)$provider->getSubmissionSummaryHtml($submission, $field);
            expect($readOnly)->toContain('Subscription')->toContain('Active')->not->toContain('payment-subscriptions/cancel');

            Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin()->one());

            $editable = (string)$provider->getSubmissionSummaryHtml($submission, $field);
            expect($editable)->toContain('payment-subscriptions/cancel');
        });
    } finally {
        Craft::$app->getUser()->setIdentity($originalIdentity);
    }
});
