<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\PaymentAttempt;
use verbb\formie\integrations\payments\Moneris;
use verbb\formie\models\Payment;
use verbb\formie\models\PaymentFieldPayload;

it('keeps Moneris outcomes durable and retries only a confirmed decline', function (string $outcome): void {
    $approved = '<response><receipt><ResponseCode>001</ResponseCode><Complete>true</Complete><TimedOut>false</TimedOut><TransID>approved-receipt</TransID></receipt></response>';
    $first = match ($outcome) {
        'approved' => new Response(200, [], $approved),
        'declined' => new Response(200, [], '<response><receipt><ResponseCode>050</ResponseCode><Complete>true</Complete><TimedOut>false</TimedOut><Message>Declined</Message></receipt></response>'),
        'timed-out' => new Response(200, [], '<response><receipt><ResponseCode>001</ResponseCode><Complete>true</Complete><TimedOut>true</TimedOut><TransID>uncertain-receipt</TransID></receipt></response>'),
        'incomplete' => new Response(200, [], '<response><receipt><ResponseCode>001</ResponseCode><Complete>false</Complete><TransID>uncertain-receipt</TransID></receipt></response>'),
        'lost-response' => new ConnectException('Response lost', new Request('POST', 'https://example.test')),
    };
    $history = [];
    $handler = HandlerStack::create(new MockHandler([$first, new Response(200, [], $approved)]));
    $handler->push(Middleware::history($history));
    $integration = new class(['name' => 'Moneris recovery', 'handle' => 'monerisRecovery' . bin2hex(random_bytes(5)), 'storeId' => 'test-store', 'apiToken' => 'test-key']) extends Moneris {
        public ?Client $fixtureClient = null;
        protected function defineClient(): Client { return $this->fixtureClient; }
        protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload {
            return new PaymentFieldPayload($this->handle, 'payment', ['monerisTokenId' => 'test-token']);
        }
    };
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => Moneris::class,
        'providerSettings' => [$integration->handle => ['currency' => 'CAD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $integration->fixtureClient = new Client(['handler' => $handler, 'base_uri' => 'https://example.test/']);
    $firstDecision = $integration->processPayment($submission);
    $firstPayment = Formie::$plugin->getPayments()->getSubmissionPayments($submission)[0];
    expect($firstPayment->status)->toBe(match ($outcome) {
        'approved' => Payment::STATUS_SUCCESS,
        'declined' => Payment::STATUS_FAILED,
        default => Payment::STATUS_PENDING,
    });
    $xml = new SimpleXMLElement((string)$history[0]['request']->getBody());
    expect((string)$xml->res_purchase_cc->order_id)->toBe((new PaymentAttempt($firstPayment))->merchantReference());
    $secondDecision = $integration->processPayment($submission);
    expect($secondDecision->status === 'succeeded')->toBe(in_array($outcome, ['approved', 'declined'], true));
    expect($history)->toHaveCount($outcome === 'declined' ? 2 : 1);
    expect(Formie::$plugin->getPayments()->getSubmissionPayments($submission))->toHaveCount($outcome === 'declined' ? 2 : 1);
})->with(['approved', 'declined', 'timed-out', 'incomplete', 'lost-response']);
