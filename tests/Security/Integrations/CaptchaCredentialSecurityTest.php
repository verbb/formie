<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\captchas\Akismet;
use verbb\formie\integrations\captchas\Recaptcha;

class CaptchaCredentialRecaptcha extends Recaptcha
{
    public ?ClientInterface $validationClient = null;

    protected function createValidationClient(): ClientInterface
    {
        return $this->validationClient ?? parent::createValidationClient();
    }
}

function captchaCredentialClient(array $responses, array &$history): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    return new Client(['handler' => $stack]);
}

it('sends reCAPTCHA Enterprise credentials in a header instead of the URL', function (): void {
    $history = [];
    $recaptcha = new CaptchaCredentialRecaptcha([
        'type' => Recaptcha::RECAPTCHA_TYPE_ENTERPRISE,
        'enterpriseType' => Recaptcha::ENTERPRISE_MODE_CHECKBOX,
        'siteKey' => 'site-key',
        'secretKey' => 'recaptcha-secret-key',
        'projectId' => 'security-project',
    ]);
    $recaptcha->validationClient = captchaCredentialClient([
        new Response(200, [], '{"tokenProperties":{"valid":true}}'),
    ], $history);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($recaptcha, &$history): void {
        $request->setBodyParams(['g-recaptcha-response' => 'browser-token']);

        expect($recaptcha->validateSubmission(new Submission()))->toBeTrue();

        $providerRequest = $history[0]['request'];
        expect((string)$providerRequest->getUri())
            ->toBe('https://recaptchaenterprise.googleapis.com/v1/projects/security-project/assessments')
            ->not->toContain('recaptcha-secret-key')
            ->and($providerRequest->getHeaderLine('X-Goog-Api-Key'))->toBe('recaptcha-secret-key');
        expect(json_decode((string)$providerRequest->getBody(), true)['event'])->toMatchArray([
            'siteKey' => 'site-key',
            'token' => 'browser-token',
        ]);
    }, ['method' => 'POST']);
})->group('security');

it('sends Akismet credentials in the documented request body', function (): void {
    $history = [];
    $akismet = new Akismet(['apiKey' => 'akismet-secret-key']);
    $akismet->setClient(captchaCredentialClient([new Response(200, [], 'false')], $history));
    $form = formie()->form(['title' => 'Akismet Credential Security'])->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => 'Hello'])->save();

    WebRequestTestHelper::withWebRequestContext(function () use ($akismet, $submission, &$history): void {
        expect($akismet->validateSubmission($submission))->toBeTrue();

        $providerRequest = $history[0]['request'];
        parse_str((string)$providerRequest->getBody(), $body);
        expect((string)$providerRequest->getUri())->toBe('https://rest.akismet.com/1.1/comment-check')
            ->and($body)->toMatchArray([
                'api_key' => 'akismet-secret-key',
                'comment_type' => 'contact-form',
            ]);
    }, ['method' => 'POST']);
})->group('security');

it('redacts captcha credentials from transport failure logs', function (): void {
    $secret = 'akismet-log-secret';
    $history = [];
    $akismet = new Akismet(['apiKey' => $secret]);
    $akismet->setClient(captchaCredentialClient([
        new ConnectException('Connection failed for https://' . $secret . '.rest.akismet.com', new Request('POST', 'https://rest.akismet.com/1.1/comment-check')),
    ], $history));
    $form = formie()->form(['title' => 'Captcha Log Security'])->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => 'Hello'])->save();
    $messageOffset = count(Craft::getLogger()->messages);

    WebRequestTestHelper::withWebRequestContext(function () use ($akismet, $submission): void {
        expect($akismet->validateSubmission($submission))->toBeFalse();
    }, ['method' => 'POST']);

    $messages = array_column(array_slice(Craft::getLogger()->messages, $messageOffset), 0);
    $captchaLog = implode("\n", array_filter($messages, fn(string $message): bool => str_contains($message, 'Captcha validation failed')));
    expect($captchaLog)
        ->toContain('[redacted]')
        ->not->toContain($secret);
})->group('security');
