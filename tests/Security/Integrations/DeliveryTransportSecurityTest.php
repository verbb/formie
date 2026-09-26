<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\models\Token;
use verbb\formie\base\Integration;
use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationSecrets;

class GuardedOAuthProviderFixture extends \verbb\auth\providers\Generic
{
    public function refreshToken(Token $token, bool $force = false): ?Token { return $token; }
    public function getBaseApiUrl(?Token $token): ?string { return 'https://example.com/api'; }
    public function getApiRequestQueryParams(?Token $token): array { return []; }
}

class GuardedOAuthIntegrationFixture extends Integration
{
    public static $transport;
    public static function displayName(): string { return 'Guarded OAuth fixture'; }
    public static function supportsOAuthConnection(): bool { return true; }
    public static function getOAuthProviderClass(): string { return GuardedOAuthProviderFixture::class; }
    public function getToken(): ?Token {
        $token = new Token();
        $token->setToken(new AccessToken(['access_token' => 'synthetic-bearer', 'expires' => time() + 3600]));
        return $token;
    }
    public function getOAuthProviderConfig(): array {
        return ['clientId' => 'synthetic-id', 'clientSecret' => 'synthetic-secret', 'urlAuthorize' => 'https://example.com/oauth/authorize', 'urlAccessToken' => 'https://example.com/oauth/token', 'urlResourceOwnerDetails' => 'https://example.com/api/me'];
    }
    public function fetchFormSettings(): \verbb\formie\models\IntegrationFormSettings { return new \verbb\formie\models\IntegrationFormSettings(); }
    protected function createDeliveryHttpHandler(): callable { return self::$transport; }
}

it('pins the actual OAuth HTTP transport and blocks origin overrides before credentials leave', function () {
    $history = [];
    $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"id":"remote-1"}')]));
    $stack->push(Middleware::history($history));
    GuardedOAuthIntegrationFixture::$transport = $stack;
    $integration = new GuardedOAuthIntegrationFixture(['name' => 'OAuth', 'handle' => 'oauth']);
    expect($integration->request('GET', 'contacts'))->toBe(['id' => 'remote-1']);
    expect($history)->toHaveCount(1);
    expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer synthetic-bearer');
    expect($history[0]['options']['allow_redirects'])->toBeFalse();
    expect($history[0]['options']['proxy'])->toBe('');
    expect($history[0]['options']['verify'])->toBeTrue();
    expect($history[0]['options']['curl'][CURLOPT_RESOLVE][0])->toStartWith('example.com:443:');
    expect(fn() => $integration->request('POST', 'https://8.8.8.8/steal'))->toThrow(\verbb\formie\errors\IntegrationStepException::class);
    expect(fn() => $integration->request('POST', 'contacts', ['base_uri' => 'https://8.8.8.8/steal']))->toThrow(\verbb\formie\errors\IntegrationException::class);
    expect($history)->toHaveCount(1);
});

it('does not expose unallowlisted environment secrets in per-form request headers', function () {
    $old = Formie::$plugin->getSettings()->referenceEnvironmentAllowlist;
    Formie::$plugin->getSettings()->referenceEnvironmentAllowlist = [];
    try {
        expect(fn() => IntegrationSecrets::resolveFormValue('$CRAFT_SECURITY_KEY'))->toThrow(RuntimeException::class);
        $integration = new \verbb\formie\integrations\automations\WebRequest(['headers' => [['key' => 'Authorization', 'value' => '$CRAFT_SECURITY_KEY']]]);
        expect(fn() => $integration->getClient())->toThrow(RuntimeException::class);
        $sealed = IntegrationSecrets::protect(['headers' => [['key' => 'Authorization', 'value' => 'literal-secret']], 'url' => 'https://example.test/secret-hook']);
        expect(json_encode($sealed))->not->toContain('literal-secret', 'secret-hook');
        expect(IntegrationSecrets::reveal($sealed)['headers'][0]['value'])->toBe('literal-secret');
    } finally { Formie::$plugin->getSettings()->referenceEnvironmentAllowlist = $old; }
});

it('rejects public request transport overrides and nonstandard ports', function (array $options, string $url) {
    $integration = new \verbb\formie\integrations\automations\WebRequest();
    expect(fn() => $integration->request('POST', $url, $options))->toThrow(\verbb\formie\errors\IntegrationException::class);
})->with([
    [['proxy' => 'http://127.0.0.1'], 'https://8.8.8.8/'],
    [['curl' => [CURLOPT_RESOLVE => ['example.com:443:127.0.0.1']]], 'https://8.8.8.8/'],
    [['headers' => ['Host' => 'internal']], 'https://8.8.8.8/'],
    [[], 'https://8.8.8.8:8443/'],
]);

it('keeps absolute CAPTCHA verification endpoints usable through the isolated public transport', function () {
    $captcha = new class extends \verbb\formie\integrations\captchas\FriendlyCaptcha {
        public static $handler;
        protected function createDeliveryHttpHandler(): callable { return self::$handler; }
    };
    $requests = [];
    $handler = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler([new \GuzzleHttp\Psr7\Response(200, [], '{"success":true}')]));
    $handler->push(\GuzzleHttp\Middleware::history($requests));
    $captcha::$handler = $handler;
    expect($captcha->request('POST', 'https://8.8.8.8/siteverify', ['json' => ['secret' => 'synthetic-captcha-secret']]))->toBe(['success' => true]);
    expect($requests)->toHaveCount(1)->and($requests[0]['options']['allow_redirects'])->toBeFalse()->and($requests[0]['options']['verify'])->toBeTrue();
});
