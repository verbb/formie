<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\models\PaymentFieldPayload;

class AvailabilityEway extends Eway
{
    public static int $calls = 0;
    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
    {
        return new PaymentFieldPayload($this->handle, 'payment', ['ewayTokenData' => ['cardNumber' => 'qa-encrypted', 'securityCode' => 'qa-encrypted', 'expiryDate' => '12/30']]);
    }
    public function request(string $method, string $uri, array $options = []): mixed
    {
        self::$calls++;
        return ['TransactionStatus' => true, 'TransactionID' => 'qa-availability', 'ResponseCode' => '00'];
    }
}

it('requires an available gateway before completing a payment form', function (string $state): void {
    AvailabilityEway::$calls = 0;
    $integration = new AvailabilityEway(['name' => 'Availability', 'handle' => 'availability' . bin2hex(random_bytes(5)), 'enabled' => $state === 'enabled']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->settings(['disableCaptchas' => true])->paymentField('payment', [
        'paymentIntegration' => $state === 'missing' ? 'removedGateway' : $integration->handle,
        'paymentIntegrationType' => AvailabilityEway::class,
        'providerSettings' => [$integration->handle => ['currency' => 'AUD', 'amountType' => 'fixed', 'amountFixed' => '25.50']],
    ])->create();
    $submission = new Submission();
    $submission->setForm($form);
    $response = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));
    expect($response->success)->toBe($state === 'enabled')
        ->and($response->submission->isIncomplete)->toBe($state !== 'enabled')
        ->and(AvailabilityEway::$calls)->toBe($state === 'enabled' ? 1 : 0);
    if ($state !== 'enabled') {
        expect($response->payment['status'])->toBe('failed');
        expect($response->submission->getErrors('payment'))->not->toBeEmpty();
    }
})->with(['enabled', 'disabled', 'missing']);
