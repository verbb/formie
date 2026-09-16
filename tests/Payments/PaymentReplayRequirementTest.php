<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

class ReplayCompletionMollie extends \verbb\formie\integrations\payments\Mollie
{
    public array $created = [];
    public function request(string $method, string $uri, array $options = []): mixed {
        if ($method === 'POST') {
            $this->created = $options['json'];
            return ['id' => 'tr_audit' . $this->id, 'status' => 'open', '_links' => ['checkout' => ['href' => 'https://example.test/checkout']]];
        }
        return ['id' => 'tr_audit' . $this->id, 'status' => 'paid', 'amount' => $this->created['amount'], 'metadata' => $this->created['metadata']];
    }
}

it('reconciles hosted payment completion with the current submission requirement', function (int $currentAmount): void {
    $integration = new ReplayCompletionMollie(['name' => 'Replay audit', 'handle' => 'replayAudit' . bin2hex(random_bytes(5)), 'apiKey' => 'test-audit']);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->numberField('total')->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => ReplayCompletionMollie::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'dynamic', 'amountVariable' => '{field:total}']],
    ])->settings(['disableCaptchas' => true])->create();
    $form->getFieldByHandle('payment')->providerSettings[$integration->handle]['amountVariable'] = '{field:' . $form->getFieldByHandle('total')->reference . '}';
    Craft::$app->getElements()->saveElement($form, false);
    $submission = formie()->submission($form)->with(['total' => 25])->save();
    $submission->isIncomplete = true;
    Craft::$app->getElements()->saveElement($submission, false);
    $integration->setField($form->getFieldByHandle('payment'));
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($integration, $submission, $currentAmount): void {
        expect($integration->getAmount($submission))->toBe(25.0);
        expect($integration->processPayment($submission)->status)->toBe('actionRequired');
        $submission->setFieldValue('total', $currentAmount);
        $retry = Formie::$plugin->getSubmissionWorkflow()->processSubmissionRequest(new \verbb\formie\models\SubmissionRequest([
            'processMode' => \verbb\formie\services\SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $submission->getForm(), 'submission' => $submission, 'submitAction' => 'submit',
        ]));
        expect($retry->paymentStatus)->toBe($currentAmount === 25 ? 'actionRequired' : 'failed');
        Craft::$app->getRequest()->setBodyParams(['id' => 'tr_audit' . $integration->id]);
        $result = $integration->processWebhook();
        $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
        expect($saved->isIncomplete)->toBe($currentAmount !== 25);
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($saved);
        expect($payments)->toHaveCount(1);
        expect($payments[0]->status)->toBe('success');
        expect($payments[0]->amount)->toBe(25.0);
        // A repeated authoritative callback preserves both the receipt and completion decision.
        $integration->processWebhook();
        $again = Submission::find()->id($saved->id)->status(null)->isIncomplete(null)->one();
        expect($again->isIncomplete)->toBe($currentAmount !== 25);
    });
})->with(['changed amount' => 250, 'unchanged amount control' => 25]);


it('compares stored payments using each provider currency unit and current settings', function (string $provider, string $currency, float $configured, float $stored, bool $matches): void {
    $class = 'verbb\\formie\\integrations\\payments\\' . $provider;
    $integration = new $class(['name' => 'Replay units', 'handle' => 'replayUnits' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => $class,
        'providerSettings' => [$integration->handle => [
            'currency' => $currency,
            'currencyType' => 'fixed',
            'currencyFixed' => $currency,
            'amountType' => 'fixed',
            'amountFixed' => $configured,
        ]],
    ])->settings(['disableCaptchas' => true])->create();
    $submission = formie()->submission($form)->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $payment = new \verbb\formie\models\Payment([
        'submissionId' => $submission->id,
        'fieldId' => $form->getFieldByHandle('payment')->id,
        'integrationId' => $integration->id,
        'amount' => $stored,
        'currency' => $currency === 'EUR' ? 'USD' : $currency,
        'status' => 'success',
        'reference' => 'verified-' . uniqid(),
    ]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $response = Formie::$plugin->getSubmissionWorkflow()->processSubmissionRequest(new \verbb\formie\models\SubmissionRequest([
        'processMode' => \verbb\formie\services\SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
    ]));
    expect($response->success)->toBe($matches);
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    expect($saved->isIncomplete)->toBe(!$matches);
    expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe('success');
})->with([
    ['Stripe', 'USD', 19.994, 19.99, true],
    ['Stripe', 'JPY', 19.1, 20, true],
    ['Stripe', 'USD', 20.99, 19.99, false],
    ['Opayo', 'GBP', 19.994, 19.99, true],
    ['Opayo', 'GBP', 20.99, 19.99, false],
    ['Eway', 'BHD', 19.9994, 19.999, true],
    ['Mollie', 'EUR', 25, 25, false],
    ['Mollie', 'USD', 25, 25, true],
]);
