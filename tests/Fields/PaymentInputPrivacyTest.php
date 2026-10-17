<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\Payment;
use verbb\formie\integrations\payments\Eway;
use verbb\formie\integrations\payments\Bpoint;
use verbb\formie\integrations\payments\Opayo;
use Tests\Support\WebRequestTestHelper;

it('keeps gateway card inputs out of native form transport', function (string $provider): void {
    $integration = new $provider(['name' => 'Privacy', 'handle' => 'privacy' . bin2hex(random_bytes(5)), 'enabled' => true] + match ($provider) {
        Eway::class => ['apiKey' => 'qa', 'apiPassword' => 'qa', 'clientSideEncryptionKey' => 'qa'],
        Bpoint::class => ['username' => 'qa', 'password' => 'qa', 'merchantNumber' => 'qa'],
        Opayo::class => ['vendorName' => 'qa', 'integrationKey' => 'qa', 'integrationPassword' => 'qa'],
    });
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => $provider])->create();
    $html = WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderField($form, 'payment'));
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $inputs = (new DOMXPath($document))->query('//input[@autocomplete="cc-number" or @autocomplete="cc-csc" or @autocomplete="cc-exp" or @autocomplete="cc-name"]');
    expect($inputs->length)->toBe(4);
    foreach ($inputs as $input) {
        expect($input->hasAttribute('name'))->toBeFalse();
    }
})->with([Eway::class, Bpoint::class, Opayo::class]);

it('keeps transient payment credentials available to the provider but out of storage and integration projections', function (): void {
    $form = formie()->form()->paymentField('payment')->create();
    $field = $form->getFieldByHandle('payment');
    $payload = ['amount' => '25.50', 'currency' => 'AUD', 'cardNumber' => 'synthetic-card', 'cardCvc' => 'synthetic-cvc', 'ewayTokenData' => 'encrypted-card-data', 'opayoSessionKey' => 'session-key', 'squarePaymentId' => 'payment-nonce'];
    $submission = formie()->submission($form)->with(['payment' => $payload])->save();
    $safe = ['amount' => '25.50', 'currency' => 'AUD'];
    expect($submission->getFieldValueAsData('payment'))->toBe($safe)
        ->and(json_decode($field->getValueAsString($payload, $submission), true))->toBe($safe)
        ->and(json_decode($field->getValueAsString($submission->getFieldValue('payment'), $submission), true))->toBe($safe)
        ->and($submission->getFieldValue('payment')->toArray())->toBe($safe)
        ->and(json_decode((string)$submission->getFieldValue('payment'), true))->toBe($safe)
        ->and($field->serializeValueForClientInput($submission->getFieldValue('payment'), $submission))->toBe($safe);
    $integration = new Eway(['handle' => 'privacy']);
    $integration->setField($field);
    $method = new ReflectionMethod(\verbb\formie\base\Payment::class, 'getPaymentFieldPayload');
    expect($method->invoke($integration, $submission)->string('ewayTokenData'))->toBe('encrypted-card-data');
    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('payment')->getAttributes())->toBe($safe);
});

it('renders payment records in the submission editor summary', function (string $provider): void {
    $integration = new $provider(['name' => 'Summary', 'handle' => 'summary' . bin2hex(random_bytes(5)), 'enabled' => true]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', ['paymentIntegration' => $integration->handle, 'paymentIntegrationType' => $provider])->create();
    $submission = formie()->submission($form)->save();
    $field = $form->getFieldByHandle('payment');
    $payment = new \verbb\formie\models\Payment(['integrationId' => $integration->id, 'submissionId' => $submission->id, 'fieldId' => $field->id, 'amount' => '42.75', 'currency' => 'AUD', 'status' => \verbb\formie\models\Payment::STATUS_SUCCESS]);
    expect(Formie::$plugin->getPayments()->savePayment($payment))->toBeTrue();
    $html = WebRequestTestHelper::withWebRequestContext(fn() => $integration->getSubmissionSummaryHtml($submission, $field));
    expect($html)->toContain('42.75', 'AUD', 'Succeeded');
})->with([Eway::class, Bpoint::class]);
