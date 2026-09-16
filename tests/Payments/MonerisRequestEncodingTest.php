<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\payments\Moneris;
use verbb\formie\models\PaymentFieldPayload;

it('keeps Moneris tokens as XML text without changing transaction parameters', function (string $token): void {
    $history = [];
    $handler = HandlerStack::create(new MockHandler([new Response(200, [], '<response><receipt><ResponseCode>0</ResponseCode><Complete>true</Complete><TransID>test-payment</TransID></receipt></response>')]));
    $handler->push(Middleware::history($history));
    $integration = new class(['name' => 'Moneris encoding', 'handle' => 'monerisEncoding' . bin2hex(random_bytes(5)), 'storeId' => 'test-store', 'apiToken' => 'test-key']) extends Moneris {
        private ?Client $_fixtureClient = null;
        public string $token;
        public function setFixtureClient(Client $client): void { $this->_fixtureClient = $client; }
        protected function defineClient(): Client { return $this->_fixtureClient; }
        protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
        {
            return new PaymentFieldPayload($this->handle, 'payment', ['monerisTokenId' => $this->token]);
        }
    };
    $integration->token = $token;
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $form = formie()->form()->paymentField('payment', [
        'paymentIntegration' => $integration->handle, 'paymentIntegrationType' => Moneris::class,
        'providerSettings' => [$integration->handle => ['currency' => 'CAD', 'amountType' => 'fixed', 'amountFixed' => 25]],
    ])->create();
    $submission = formie()->submission($form)->save();
    $integration->setField($form->getFieldByHandle('payment'));
    $integration->fixtureClient = new Client(['handler' => $handler, 'base_uri' => 'https://example.test/']);
    expect($integration->processPayment($submission)->status)->toBe('succeeded');
    expect($history)->toHaveCount(1);
    $body = (string)$history[0]['request']->getBody();
    expect($body)->toContain('<request>');
    $xml = new SimpleXMLElement($body);
    expect($xml->xpath('/request/res_purchase_cc/amount'))->toHaveCount(1);
    expect((string)$xml->res_purchase_cc->amount)->toBe('25.00')
        ->and((string)$xml->res_purchase_cc->data_key)->toBe($token);
})->with(['ordinary-token', 'safe</data_key><amount>0.01</amount><data_key>ignored']);
