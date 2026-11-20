<?php
// Captcha providers' verification endpoints for tests/browser/client-captchas.spec.ts. A token is valid only when it's
// the one the fake browser scripts issue, so a submission passes only if the token reached the server. Other requests
// go out as usual.
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

Craft::$container->set(Client::class, static function($container, array $params, array $config): Client {
    $options = $params[0] ?? $config;
    $stack = HandlerStack::create($options['handler'] ?? null);
    $stack->push(static fn(callable $handler) => static function(RequestInterface $request, array $requestOptions) use ($handler) {
        $url = (string)$request->getUri();
        $valid = str_contains((string)$request->getBody(), 'browser-captcha-token');
        $body = match (true) {
            str_contains($url, 'recaptchaenterprise.googleapis.com') => ['tokenProperties' => ['valid' => $valid, 'action' => 'submit'], 'riskAnalysis' => ['score' => 0.9]],
            str_contains($url, 'recaptcha.net/recaptcha/api/siteverify') => ['success' => $valid, 'score' => 0.9, 'action' => 'submit'],
            str_contains($url, 'hcaptcha.com/siteverify'), str_contains($url, 'challenges.cloudflare.com/turnstile/v0/siteverify'), str_contains($url, 'captcha.eu/validate') => ['success' => $valid],
            default => null,
        };

        return $body === null ? $handler($request, $requestOptions) : Create::promiseFor(new Response(200, ['Content-Type' => 'application/json'], json_encode($body)));
    });
    $options['handler'] = $stack;

    return new Client($options);
});

// Switches the reCAPTCHA type for the next test. Only the four supported types are accepted.
if ($path === '/browser-captcha-setup' && in_array($_GET['recaptcha'] ?? '', ['v2_checkbox', 'v2_invisible', 'v3', 'enterprise'], true)) {
    // Saving spam protection settings needs a user who may manage them, as in the control panel.
    Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin()->one());
    $integrations = \verbb\formie\Formie::$plugin->getIntegrations();
    $captcha = $integrations->getCaptchaByHandle('recaptcha');
    $captcha->type = $_GET['recaptcha'];
    $integrations->saveCaptcha($captcha);
    echo 'ok';
    exit;
}
