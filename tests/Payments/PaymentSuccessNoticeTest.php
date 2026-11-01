<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\PaymentStatusController;
use verbb\formie\Formie;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\services\Service;

class PaymentSuccessNoticeRecordingService extends Service
{
    public array $flashes = [];

    public function setFlash(string $namespace, string $key, mixed $value, bool $removeAfterAccess = true): void
    {
        $this->flashes[$key] = $value;
    }
}

it('stores the same sanitized success notice as other completion paths after a payment', function (): void {
    $integration = new Mollie(['name' => 'Success Notice Integration ' . uniqid(), 'handle' => 'successNotice' . uniqid(), 'enabled' => false]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration, false);

    $form = formie()
        ->form(['title' => 'Payment Success Notice'])
        ->singleLineTextField('fullName')
        ->paymentField('paymentField', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => get_class($integration)])
        ->create();
    $form->setSettings(['successMessage' => '<p>Paid. <a href="https://example.test/terms" target="_blank">Terms</a></p>']);
    expect(Formie::$plugin->getForms()->saveForm($form))->toBeTrue();

    $submission = formie()->submission($form)->with(['fullName' => 'Payment Fixture'])->save();
    $payment = new PaymentModel([
        'integrationId' => $integration->id,
        'submissionId' => $submission->id,
        'fieldId' => $form->getFieldByHandle('paymentField')->id,
        'amount' => 10.00,
        'currency' => 'AUD',
        'status' => PaymentModel::STATUS_SUCCEEDED,
        'reference' => 'success-notice-' . uniqid(),
    ]);
    Formie::$plugin->getPayments()->savePayment($payment, false);

    $raw = $form->settings->getSuccessMessage($submission);
    $statusToken = PaymentAccess::issueStatusToken($payment);

    WebRequestTestHelper::withWebRequestContext(function () use ($raw): void {
        $service = new PaymentSuccessNoticeRecordingService();
        Formie::$plugin->set('service', $service);

        $response = (new PaymentStatusController('payment-status', Craft::$app))->actionPollStatus();

        expect($response->data['status'])->toBe('success')
            ->and($raw)->toContain('target=')
            ->and($service->flashes['notice'])->toBe(StringHelper::sanitizeMessageHtml($raw))
            ->and($service->flashes['notice'])->not->toContain('target=')
            ->and($service->flashes['notice'])->toContain('href="https://example.test/terms"');
    }, ['queryParams' => ['statusToken' => $statusToken]]);
});
