<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationException;
use verbb\formie\integrations\captchas\FriendlyCaptcha;

class ProxyCaptchaFixture extends FriendlyCaptcha
{
    public $transport;

    protected function createDeliveryHttpHandler(): callable
    {
        return $this->transport;
    }
}

beforeEach(function () {
    $this->previousHttpsProxy = getenv('HTTPS_PROXY');
    $this->previousHttpProxy = Craft::$app->getConfig()->getGeneral()->httpProxy;
    putenv('HTTPS_PROXY=http://proxy.invalid:3128');
    Craft::$app->getConfig()->getGeneral()->httpProxy = null;
});

afterEach(function () {
    putenv($this->previousHttpsProxy === false ? 'HTTPS_PROXY' : 'HTTPS_PROXY=' . $this->previousHttpsProxy);
    Craft::$app->getConfig()->getGeneral()->httpProxy = $this->previousHttpProxy;
});

it('preserves environment proxies without local DNS pins for built-in captcha endpoints', function (string $endpoint) {
    $history = [];
    $captcha = new ProxyCaptchaFixture();
    $captcha->transport = HandlerStack::create(new MockHandler([new Response(200, [], '{"success":true}')]));
    $captcha->transport->push(Middleware::history($history));

    expect($captcha->request('POST', $endpoint, ['json' => ['response' => 'browser-token']]))->toBe(['success' => true]);
    expect($history)->toHaveCount(1)
        ->and($history[0]['options']['proxy']['https'] ?? null)->toBe('http://proxy.invalid:3128')
        ->and($history[0]['options']['curl'][CURLOPT_RESOLVE] ?? null)->toBeNull()
        ->and($history[0]['options']['allow_redirects'])->toBeFalse()
        ->and($history[0]['options']['verify'])->toBeTrue();
})->with([
    'Friendly Captcha v1' => 'https://api.friendlycaptcha.com/api/v1/siteverify',
    'Friendly Captcha v2' => 'https://global.frcapi.com/api/v2/captcha/siteverify',
    'hCaptcha' => 'https://hcaptcha.com/siteverify',
    'Turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    'CleanTalk' => 'https://moderate.cleantalk.org/api2.0',
    'OopSpam' => 'https://api.oopspam.com/v1/spamdetection',
    'Captcha.eu default' => 'https://www.captcha.eu/validate',
]);

it('preserves Craft proxies and does not follow captcha provider redirects', function () {
    Craft::$app->getConfig()->getGeneral()->httpProxy = 'http://craft-proxy.invalid:3128';
    $history = [];
    $captcha = new ProxyCaptchaFixture();
    $captcha->transport = HandlerStack::create(new MockHandler([new Response(302, ['Location' => 'http://127.0.0.1/'], 'redirect')]));
    $captcha->transport->push(Middleware::history($history));

    expect(fn() => $captcha->request('POST', 'https://api.friendlycaptcha.com/api/v1/siteverify'))->toThrow(RequestException::class, 'Integration redirects are not permitted.');
    expect($history)->toHaveCount(1)
        ->and($history[0]['options']['proxy'])->toBe('http://craft-proxy.invalid:3128');
});

it('keeps custom captcha endpoints on the restricted transport', function () {
    $history = [];
    $captcha = new ProxyCaptchaFixture();
    $captcha->transport = HandlerStack::create(new MockHandler([new Response(200, [], '{"success":true}')]));
    $captcha->transport->push(Middleware::history($history));

    expect($captcha->request('POST', 'https://8.8.8.8/validate'))->toBe(['success' => true]);
    expect($history[0]['options']['proxy'])->toBe('');
    expect(fn() => $captcha->request('POST', 'http://127.0.0.1/validate'))->toThrow(IntegrationException::class);
    expect($history)->toHaveCount(1);
});

it('validates Friendly Captcha submissions through the configured proxy', function (string $version, string $tokenName, bool $success) {
    $history = [];
    $captcha = new ProxyCaptchaFixture([
        'apiVersion' => $version,
        'siteKey' => 'test-site-key',
        'secretKey' => 'test-secret-key',
    ]);
    $captcha->transport = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(['success' => $success]))]));
    $captcha->transport->push(Middleware::history($history));
    $submission = new Submission();
    $submission->setCaptchaData($tokenName, ['value' => 'browser-token']);

    expect($captcha->validateSubmission($submission))->toBe($success)
        ->and($history)->toHaveCount(1)
        ->and($history[0]['options']['proxy']['https'] ?? null)->toBe('http://proxy.invalid:3128');
    $payload = json_decode((string)$history[0]['request']->getBody(), true);
    expect($payload[$version === 'v1' ? 'solution' : 'response'])->toBe('browser-token');
})->with([
    ['v1', 'frc-captcha-solution', true],
    ['v2', 'frc-captcha-response', true],
    ['v1', 'frc-captcha-solution', false],
    ['v2', 'frc-captcha-response', false],
]);

it('requires the exact built-in verification URL for proxy eligibility', function (string $endpoint) {
    $captcha = new ProxyCaptchaFixture();
    $method = new ReflectionMethod($captcha, 'isTrustedPublicEndpoint');
    expect($method->invoke($captcha, $endpoint))->toBeFalse();
})->with([
    'http://api.friendlycaptcha.com/api/v1/siteverify',
    'https://api.friendlycaptcha.com.evil.invalid/api/v1/siteverify',
    'https://api.friendlycaptcha.com@127.0.0.1/api/v1/siteverify',
    'https://api.friendlycaptcha.com:8443/api/v1/siteverify',
    'https://api.friendlycaptcha.com/api/v1/siteverify?target=internal',
    'https://api.friendlycaptcha.com/other',
    'https://custom.captcha.invalid/validate',
]);

it('keeps built-in verification usable without a proxy', function () {
    putenv('HTTPS_PROXY');
    $history = [];
    $captcha = new ProxyCaptchaFixture();
    $captcha->transport = HandlerStack::create(new MockHandler([new Response(200, [], '{"success":true}')]));
    $captcha->transport->push(Middleware::history($history));

    expect($captcha->request('POST', 'https://api.friendlycaptcha.com/api/v1/siteverify'))->toBe(['success' => true])
        ->and($history[0]['options']['proxy']['https'] ?? null)->toBeNull()
        ->and($history[0]['options']['verify'])->toBeTrue();
});

it('rejects request-level transport overrides even for built-in captcha endpoints', function (array $options) {
    $captcha = new ProxyCaptchaFixture();
    expect(fn() => $captcha->request('POST', 'https://api.friendlycaptcha.com/api/v1/siteverify', $options))->toThrow(IntegrationException::class);
})->with([
    [['proxy' => 'http://127.0.0.1:3128']],
    [['curl' => [CURLOPT_RESOLVE => ['api.friendlycaptcha.com:443:127.0.0.1']]]],
    [['verify' => false]],
    [['headers' => ['Host' => 'internal']]],
]);
