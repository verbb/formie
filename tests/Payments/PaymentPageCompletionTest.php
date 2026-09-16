<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\models\PaymentFieldPayload;

class PageCompletionEway extends Eway
{
    public array $requests = [];
    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload {
        return new PaymentFieldPayload($this->handle, 'payment', ['ewayTokenData' => ['cardNumber' => 'test-encrypted-card', 'securityCode' => 'test-cvn', 'expiryDate' => '12/30']]);
    }
    public function request(string $method, string $uri, array $options = []): mixed {
        $this->requests[] = [$method, $uri, $options];
        return ['TransactionStatus' => true, 'TransactionID' => 'test-' . uniqid(), 'ResponseCode' => '00'];
    }
}


it('requires every enabled payment field to be on the final submission page', function (int $paymentPage): void {
    $integration = new PageCompletionEway(['name' => 'Page audit', 'handle' => 'pageAudit' . bin2hex(random_bytes(5))]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);
    $form = formie()->form()->multiPage(2)->onPage($paymentPage)->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => PageCompletionEway::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->onPage($paymentPage === 1 ? 2 : 1)->singleLineTextField('note')->settings(['disableCaptchas' => true])->create();
    $submission = new Submission();
    $submission->setForm($form);
    $pages = $form->getPages();
    $workflow = Formie::$plugin->getSubmissionWorkflow();
    $response = null;
    foreach ($pages as $page) {
        $response = $workflow->processSubmissionRequest(new \verbb\formie\models\SubmissionRequest([
            'processMode' => \verbb\formie\services\SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form, 'submission' => $submission, 'submitAction' => 'submit', 'pageId' => (int)$page->id,
        ]));
        $submission = $response->submission;
    }
    $count = count(Formie::$plugin->getPayments()->getSubmissionPayments($submission));
    if ($paymentPage === 2) {
        expect($response->success)->toBeTrue();
        expect($submission->isIncomplete)->toBeFalse();
        expect($count)->toBe(1);
    }
    if ($paymentPage === 1) {
        expect($response->success)->toBeFalse();
        expect($response->paymentStatus)->toBe('failed');
        expect($response->paymentMessage)->toContain('final page');
        expect($submission->isIncomplete)->toBeTrue();
        expect($count)->toBe(0);
    }
})->with(['earlier page' => 1, 'final page control' => 2]);

it('checks all payment placements before sending any purchase', function (bool $disabled): void {
    $integration = new PageCompletionEway(['name' => 'Multiple payment pages', 'handle' => 'multiPayment' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $settings = [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => PageCompletionEway::class,
        'providerSettings' => [$integration->handle => ['currency' => 'USD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ];
    $form = formie()->form()->multiPage(2)
        ->onPage(1)->paymentField('earlier', $settings + ['enabled' => !$disabled])
        ->onPage(2)->paymentField('finalPayment', $settings)
        ->settings(['disableCaptchas' => true])->create();
    $submission = new Submission();
    $submission->setForm($form);
    $pages = $form->getPages();
    $response = Formie::$plugin->getSubmissionWorkflow()->processSubmissionRequest(new \verbb\formie\models\SubmissionRequest([
        'processMode' => \verbb\formie\services\SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => 'submit',
        'pageId' => (int)$pages[1]->id,
    ]));
    expect($response->success)->toBe($disabled);
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    expect($saved->isIncomplete)->toBe(!$disabled);
    expect(Formie::$plugin->getPayments()->getSubmissionPayments($saved))->toHaveCount($disabled ? 1 : 0);
})->with([false, true]);
