<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\models\payments\PaymentWebhookCommand;
use yii\web\HttpException;

it('rejects oversized webhook bodies with HTTP 413', function(bool $declared) {
    WebRequestTestHelper::withWebRequestContext(function($request) use ($declared) {
        $request->getHeaders()->set('Content-Length', $declared ? PaymentWebhookCommand::MAX_BODY_BYTES + 1 : 0);
        $request->setRawBody($declared ? '{}' : str_repeat('x', PaymentWebhookCommand::MAX_BODY_BYTES + 1));
        try {
            PaymentWebhookCommand::fromRequest(1);
            test()->fail('Oversized request was accepted.');
        } catch (HttpException $exception) {
            expect($exception->statusCode)->toBe(413);
        }
    });
})->with([true, false]);

it('accepts webhook bodies at the size limit', function() {
    WebRequestTestHelper::withWebRequestContext(function($request) {
        $body = str_repeat('x', PaymentWebhookCommand::MAX_BODY_BYTES);
        $request->setRawBody($body);
        $request->setBodyParams([]);
        $request->getHeaders()->set('Content-Length', strlen($body));
        expect(PaymentWebhookCommand::fromRequest(1)->body)->toBe($body);
    });
});
