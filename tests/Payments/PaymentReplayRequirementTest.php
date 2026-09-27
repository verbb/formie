<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

it('preserves accepted content through the real payment replay boundary and duplicate receipt', function (string $status): void {
    $integration = new \verbb\formie\integrations\payments\Mollie(['name' => 'Replay content', 'handle' => 'replayContent' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->singleLineTextField('control')->singleLineTextField('detail', [
        'enableConditions' => true, 'conditions' => ['showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [
            ['field' => 'control', 'condition' => '=', 'value' => 'show'],
        ]],
    ])->hiddenField('acceptedDate', ['valueSource' => 'dateInt'])->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => get_class($integration),
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => '25']],
    ])->settings(['disableCaptchas' => true])->create();
    Formie::$plugin->getRendering()->populateFormValues($form, ['detail' => 'server value'], true);
    $submission = new Submission(['isIncomplete' => true]); $submission->setForm($form);
    $submission->setFieldValue('control', 'hide');
    (new \verbb\formie\services\RuntimeConfiguration())->applyValues($submission);
    (new \verbb\formie\conditions\ConditionVisibility())->clear($submission);
    // Model an accepted record whose provider callback arrives on a later date.
    $submission->setFieldValue('acceptedDate', '01/01/2000');
    expect($submission->getFieldValue('detail'))->toBe('');
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $payment = new \verbb\formie\models\Payment([
        'submissionId' => $submission->id, 'fieldId' => $form->getFieldByHandle('payment')->id,
        'integrationId' => $integration->id, 'amount' => '25.00', 'currency' => 'USD',
        'status' => $status, 'reference' => 'accepted-' . bin2hex(random_bytes(5)),
    ]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $dispatched = [];
    $observe = function ($event) use (&$dispatched) {
        if ($event->stage === 'dispatch') {
            $dispatched[] = [$event->command->submission->getFieldValue('detail'), $event->command->submission->getFieldValue('acceptedDate')];
        }
    };
    \yii\base\Event::on(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    try {
        $processor = Formie::$plugin->getSubmissionProcessor();
        $first = $processor->executePaymentReplay($payment);
        $again = $processor->executePaymentReplay($payment);
        $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
        expect($saved->getFieldValue('detail'))->toBe('')->and($saved->getFieldValue('acceptedDate'))->toBe('01/01/2000')
            ->and($saved->isIncomplete)->toBe($status !== 'success')
            ->and($again->response->outcome)->toEqual($first->response->outcome)
            ->and($dispatched)->toBe($status === 'success' ? [['', '01/01/2000']] : []);
    } finally {
        \yii\base\Event::off(\verbb\formie\services\SubmissionWorkflow::class, \verbb\formie\services\SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
})->with(['success', 'pending']);

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
        expect($integration->getAmount($submission))->toBe('25');
        expect($integration->processPayment($submission)->status->value)->toBe('actionRequired');
        $submission->setFieldValue('total', $currentAmount);
        $retry = runSubmissionCommand(submissionCommand([
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'form' => $submission->getForm(), 'submission' => $submission, 'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));
        expect($retry->paymentStatus)->toBe($currentAmount === 25 ? 'actionRequired' : 'failed');
        parse_str(parse_url($integration->created['webhookUrl'], PHP_URL_QUERY), $webhookQuery);
        Craft::$app->getRequest()->setQueryParams($webhookQuery);
        Craft::$app->getRequest()->setRawBody(http_build_query(['id' => 'tr_audit' . $integration->id]));
        Craft::$app->getRequest()->setBodyParams(['id' => 'tr_audit' . $integration->id]);
        $result = $integration->processWebhook();
        $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
        expect($saved->isIncomplete)->toBe($currentAmount !== 25);
        $payments = Formie::$plugin->getPayments()->getSubmissionPayments($saved);
        expect($payments)->toHaveCount(1);
        expect($payments[0]->status)->toBe('success');
        expect($payments[0]->amount)->toBe('25.00');
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
    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
    ]));
    expect($response->success)->toBe($matches);
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    expect($saved->isIncomplete)->toBe(!$matches);
    expect(Formie::$plugin->getPayments()->getPaymentById($payment->id)->status)->toBe('success');
})->with([
    ['Stripe', 'USD', 19.994, 19.99, false],
    ['Stripe', 'JPY', 19.1, 20, false],
    ['Stripe', 'USD', 20.99, 19.99, false],
    ['Opayo', 'GBP', 19.994, 19.99, false],
    ['Opayo', 'GBP', 20.99, 19.99, false],
    ['Eway', 'BHD', 19.9994, 19.999, false],
    ['Mollie', 'EUR', 25, 25, false],
    ['Mollie', 'USD', 25, 25, true],
]);
