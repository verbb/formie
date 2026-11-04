<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\SpamProtectionController;
use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\integrations\captchas\Akismet;
use verbb\formie\integrations\captchas\CleanTalk;
use verbb\formie\integrations\captchas\OopSpam;
use verbb\formie\integrations\captchas\Turnstile;
use yii\web\BadRequestHttpException;

beforeEach(function(): void {
    $this->captchaTransaction = Craft::$app->getDb()->beginTransaction();
    SpamProtectionSettings::resetCaches();
});

afterEach(function(): void {
    $this->captchaTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new \craft\db\Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    SpamProtectionSettings::resetCaches();

    // The provider store separately caches database rows, which a rollback can't invalidate.
    $providers = Formie::$plugin->getCaptchaProviders();
    (new ReflectionMethod($providers, '_resetCache'))->invoke($providers);
    Formie::$plugin->getIntegrations()->resetCaptchaCaches();
});

function saveCaptchaProvider(string $providerClass, string $credential): void
{
    $provider = null;

    foreach (Formie::$plugin->getIntegrations()->getAllCaptchas() as $candidate) {
        if ($candidate instanceof $providerClass) {
            $provider = $candidate;

            break;
        }
    }

    expect($provider)->not->toBeNull();

    WebRequestTestHelper::withWebRequestContext(function($request) use ($provider, $credential): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $settings = $provider->getSettings();

        foreach (['apiKey', 'siteKey', 'secretKey'] as $key) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = $credential;
            }
        }

        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'handle' => $provider->handle,
            'token' => SpamProtectionSettings::token('captcha:' . $provider->handle),
            'provider' => ['enabled' => '1', 'settings' => $settings],
        ]);

        (new SpamProtectionController('spam-protection', Formie::$plugin))->runAction('save-captcha');
    }, ['method' => 'POST']);
}

function captchaProviderHandle(string $providerClass): string
{
    foreach (Formie::$plugin->getIntegrations()->getAllCaptchas() as $candidate) {
        if ($candidate instanceof $providerClass) {
            return $candidate->handle;
        }
    }

    throw new RuntimeException('Provider unavailable.');
}

$providers = [
    'Akismet' => [Akismet::class],
    'CleanTalk' => [CleanTalk::class],
    'OopSpam' => [OopSpam::class],
    'Turnstile' => [Turnstile::class],
];

it('rejects enabling a CAPTCHA provider without its credentials and keeps the saved record', function (string $providerClass): void {
    $key = 'captcha:' . captchaProviderHandle($providerClass);
    $before = SpamProtectionSettings::record($key);
    $failure = null;

    try {
        saveCaptchaProvider($providerClass, '');
    } catch (Throwable $exception) {
        $failure = $exception;
    }

    expect($failure)->toBeInstanceOf(BadRequestHttpException::class)
        ->and($failure->statusCode)->toBe(400)
        ->and($failure->getMessage())->toBe('Complete the required CAPTCHA settings before enabling this provider.')
        ->and(SpamProtectionSettings::record($key))->toEqual($before);
})->with($providers);

it('saves an enabled CAPTCHA provider with its credentials', function (string $providerClass): void {
    $key = 'captcha:' . captchaProviderHandle($providerClass);

    saveCaptchaProvider($providerClass, 'test-credential');

    $record = SpamProtectionSettings::record($key);

    expect($record)->not->toBeNull()
        ->and((bool)$record['enabled'])->toBeTrue()
        ->and((string)$record['settings'])->toContain('test-credential');
})->with($providers);

it('reports whether an API key provider has its key', function (string $providerClass): void {
    expect((new $providerClass(['apiKey' => null]))->hasValidSettings())->toBeFalse()
        ->and((new $providerClass(['apiKey' => '']))->hasValidSettings())->toBeFalse()
        ->and((new $providerClass(['apiKey' => 'test-credential']))->hasValidSettings())->toBeTrue();
})->with([
    'Akismet' => [Akismet::class],
    'CleanTalk' => [CleanTalk::class],
    'OopSpam' => [OopSpam::class],
]);
