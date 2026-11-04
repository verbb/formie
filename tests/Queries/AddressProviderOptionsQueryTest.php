<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\fields\Address;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\addressproviders\Google;
use verbb\formie\integrations\captchas\Turnstile;
use yii\web\Response;

function addressProviderSaveThroughCp(string $handle, string $enabled): Response
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($handle, $enabled): Response {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'type' => Google::class,
            'name' => 'Address Provider ' . $handle,
            'handle' => $handle,
            'enabled' => $enabled,
            'scope' => 'site',
            'types' => [Google::class => ['apiKey' => 'test-only']],
        ]);

        return (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
    }, ['method' => 'POST']);
}

function addressProviderChildHandles(): array
{
    return array_column(array_merge(...array_column((new Address())->getSubFields(), 'fields')), 'handle');
}

function addressProviderClearRows(): void
{
    // Other tests may leave providers behind; each case decides what is enabled.
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_INTEGRATIONS, ['type' => Google::class])->execute();
    Formie::$plugin->set('integrations', new \verbb\formie\services\Integrations());
}

it('offers autocomplete from a provider enabled through the control panel', function (): void {
    addressProviderClearRows();
    expect(addressProviderChildHandles())->not->toContain('autoComplete');

    $handle = 'addressProvider' . bin2hex(random_bytes(4));
    $response = addressProviderSaveThroughCp($handle, '1');
    $provider = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

    expect($response->getStatusCode())->toBe(302)
        ->and($provider)->toBeInstanceOf(Google::class)
        ->and($provider->getEnabled())->toBeTrue()
        ->and(addressProviderChildHandles())->toContain('autoComplete');
});

it('does not offer autocomplete from a provider disabled through the control panel', function (): void {
    addressProviderClearRows();

    $handle = 'addressProvider' . bin2hex(random_bytes(4));
    addressProviderSaveThroughCp($handle, '0');
    $provider = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle);

    expect($provider)->toBeInstanceOf(Google::class)
        ->and($provider->getEnabled())->toBeFalse()
        ->and(addressProviderChildHandles())->not->toContain('autoComplete');
});

it('follows the provider enabled state for stored values', function (string $stored, bool $expected): void {
    addressProviderClearRows();
    putenv('FORMIE_ADDRESS_PROVIDER_ON=true');
    putenv('FORMIE_ADDRESS_PROVIDER_OFF=false');

    $handle = 'addressProvider' . bin2hex(random_bytes(4));
    addressProviderSaveThroughCp($handle, '1');
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_INTEGRATIONS, ['enabled' => $stored], ['handle' => $handle])->execute();
    Formie::$plugin->set('integrations', new \verbb\formie\services\Integrations());

    try {
        expect(Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)->getEnabled())->toBe($expected);

        if ($expected) {
            expect(addressProviderChildHandles())->toContain('autoComplete');
        } else {
            expect(addressProviderChildHandles())->not->toContain('autoComplete');
        }
    } finally {
        putenv('FORMIE_ADDRESS_PROVIDER_ON');
        putenv('FORMIE_ADDRESS_PROVIDER_OFF');
    }
})->with([
    'one' => ['1', true],
    'zero' => ['0', false],
    'true' => ['true', true],
    'false' => ['false', false],
    'enabled environment variable' => ['$FORMIE_ADDRESS_PROVIDER_ON', true],
    'disabled environment variable' => ['$FORMIE_ADDRESS_PROVIDER_OFF', false],
    'missing environment variable' => ['$FORMIE_ADDRESS_PROVIDER_MISSING', false],
]);

it('does not offer autocomplete from other enabled integrations', function (): void {
    addressProviderClearRows();

    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_INTEGRATIONS, [
        'name' => 'Enabled Captcha',
        'handle' => 'enabledCaptcha' . bin2hex(random_bytes(4)),
        'type' => Turnstile::class,
        'enabled' => '1',
        'sortOrder' => 99,
        'settings' => '{}',
        'dateCreated' => '2026-01-01 00:00:00',
        'dateUpdated' => '2026-01-01 00:00:00',
        'uid' => \craft\helpers\StringHelper::UUID(),
    ])->execute();

    expect(addressProviderChildHandles())->not->toContain('autoComplete');
});
