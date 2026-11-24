<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\Formie;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\FieldAccess;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

const SUMMARY_CROSS_ORIGIN_FRONTEND = 'https://frontend.example.test';

function summaryCrossOriginToken(string $name): string
{
    $form = formie()
        ->form(['title' => 'Summary Cross Origin'])
        ->singleLineTextField('fullName')
        ->summaryField('summary')
        ->create();
    $submission = formie()->submission($form)->with(['fullName' => $name])->save();

    return (string)FieldAccess::issueAccessToken($submission, (int)$form->getFieldByHandle('summary')->id);
}

function withSummaryAllowedOrigins(callable $callback): void
{
    $settings = Formie::$plugin->getSettings();
    $original = $settings->allowedOrigins;
    $settings->allowedOrigins = [SUMMARY_CROSS_ORIGIN_FRONTEND];

    try {
        $callback();
    } finally {
        $settings->allowedOrigins = $original;
    }
}

it('answers a summary preflight from an allowed origin', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request, $response): void {
        withSummaryAllowedOrigins(function () use ($response): void {
            $result = (new FieldsController('fields', Formie::$plugin))->runAction('get-summary-html');

            expect($result)->toBeNull()
                ->and($response->getStatusCode())->toBe(204)
                ->and($response->getHeaders()->get('Access-Control-Allow-Origin'))->toBe(SUMMARY_CROSS_ORIGIN_FRONTEND)
                ->and($response->getHeaders()->get('Access-Control-Allow-Headers'))->toContain('X-Formie-Profile', 'X-Formie-Session');
        });
    }, ['method' => 'OPTIONS', 'headers' => [
        'Origin' => SUMMARY_CROSS_ORIGIN_FRONTEND,
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'accept, x-formie-profile, x-formie-session',
    ]]);
});

it('renders a summary for an allowed origin using the cross-origin profile', function (): void {
    $accessToken = summaryCrossOriginToken('Ada Remote');

    WebRequestTestHelper::withWebRequestContext(function ($request, $response) use ($accessToken): void {
        withSummaryAllowedOrigins(function () use ($request, $response): void {
            // The public session comes from the form's render request in the browser.
            BrowserRequestProfile::enter(true);
            $request->getHeaders()->set('X-Formie-Session', (string)$response->getHeaders()->get('X-Formie-Session'));

            $html = (new FieldsController('fields', Formie::$plugin))->runAction('get-summary-html');

            expect($html)->toBeString()->toContain('Ada Remote', 'data-formie-summary-container')
                ->and($response->getHeaders()->get('Access-Control-Allow-Origin'))->toBe(SUMMARY_CROSS_ORIGIN_FRONTEND);
        });
    }, ['method' => 'POST', 'bodyParams' => ['accessToken' => $accessToken], 'headers' => [
        'Accept' => 'text/html',
        'Origin' => SUMMARY_CROSS_ORIGIN_FRONTEND,
        'X-Formie-Profile' => 'cross-origin-public',
    ]]);
});

it('rejects a summary request from an origin that is not allowed', function (): void {
    $accessToken = summaryCrossOriginToken('Not Shared');

    WebRequestTestHelper::withWebRequestContext(function (): void {
        withSummaryAllowedOrigins(function (): void {
            expect(fn() => (new FieldsController('fields', Formie::$plugin))->runAction('get-summary-html'))
                ->toThrow(ForbiddenHttpException::class);
        });
    }, ['method' => 'POST', 'bodyParams' => ['accessToken' => $accessToken], 'headers' => [
        'Accept' => 'text/html',
        'Origin' => 'https://untrusted.example.test',
        'X-Formie-Profile' => 'cross-origin-public',
    ]]);
});

it('rejects a cross-origin summary request without a known public session', function (?string $session): void {
    $accessToken = summaryCrossOriginToken('No Session');
    $headers = [
        'Accept' => 'text/html',
        'Origin' => SUMMARY_CROSS_ORIGIN_FRONTEND,
        'X-Formie-Profile' => 'cross-origin-public',
    ];

    if ($session !== null) {
        $headers['X-Formie-Session'] = $session;
    }

    // The CSRF exemption depends on this session check.
    WebRequestTestHelper::withWebRequestContext(function (): void {
        withSummaryAllowedOrigins(function (): void {
            expect(fn() => (new FieldsController('fields', Formie::$plugin))->runAction('get-summary-html'))
                ->toThrow(ForbiddenHttpException::class);
        });
    }, ['method' => 'POST', 'bodyParams' => ['accessToken' => $accessToken], 'headers' => $headers]);
})->with([
    'missing session' => [null],
    'unknown session' => ['not-a-formie-session'],
]);

it('keeps requiring CSRF for same-origin summary requests', function (): void {
    $accessToken = summaryCrossOriginToken('Same Origin');

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($accessToken): void {
        expect(fn() => (new FieldsController('fields', Formie::$plugin))->runAction('get-summary-html'))
            ->toThrow(BadRequestHttpException::class, 'Unable to verify your data submission.');

        $request->setBodyParams(['accessToken' => $accessToken, $request->csrfParam => $request->getCsrfToken()]);
        $html = (new FieldsController('fields', Formie::$plugin))->runAction('get-summary-html');

        expect($html)->toBeString()->toContain('Same Origin');
    }, ['method' => 'POST', 'bodyParams' => ['accessToken' => $accessToken], 'headers' => [
        'Accept' => 'text/html',
        'Origin' => 'https://craft.example.test',
        'X-Formie-Profile' => 'same-origin-browser',
    ]]);
});
