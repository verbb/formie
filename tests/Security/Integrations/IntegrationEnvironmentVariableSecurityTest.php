<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\Formie;
use verbb\formie\helpers\SpamProtectionEditor;
use verbb\formie\integrations\captchas\CaptchaEu;
use verbb\formie\integrations\crm\ActiveCampaign;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;

// Environment variables can hold any server secret, so only admins can use them in integration and CAPTCHA
// settings. Saved credentials a user can't see also stay with the server an admin pointed them at.

function environmentVariableActor(bool $admin, callable $callback): mixed
{
    $suffix = bin2hex(random_bytes(5));
    $actor = new User(['username' => 'envSettings' . $suffix, 'email' => 'envSettings' . $suffix . '@example.test', 'admin' => $admin]);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_INTEGRATIONS, Permissions::PERM_ACCESS_SPAM_PROTECTION, Permissions::PERM_MANAGE_CAPTCHAS,
    ])))->toBeTrue();

    $projectConfig = Craft::$app->getProjectConfig();
    $result = null;

    WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $callback, $projectConfig, &$result): void {
        Craft::$app->set('projectConfig', $projectConfig);
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($actor->id)->status(null)->one());
        $result = $callback($request);
    }, ['method' => 'POST', 'requestUri' => '/admin/actions/formie/integrations/save-integration', 'headers' => ['Accept' => 'application/json']]);

    return $result;
}

function environmentVariableIntegration(array $settings): ActiveCampaign
{
    $integration = new ActiveCampaign(['name' => 'Environment settings', 'handle' => 'envSettings' . bin2hex(random_bytes(5)), ...$settings]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();

    return $integration;
}

function environmentVariablePost($request, ?ActiveCampaign $integration, array $settings, string $action = 'save-integration'): mixed
{
    $request->setBodyParams([
        $request->csrfParam => $request->getCsrfToken(),
        'id' => $integration?->id, 'name' => $integration?->name ?? 'New integration', 'handle' => $integration?->handle ?? 'envSettingsNew' . bin2hex(random_bytes(5)),
        'type' => ActiveCampaign::class, 'enabled' => true,
        'types' => [ActiveCampaign::class => $settings],
    ]);

    return (new IntegrationsController('integrations', Formie::$plugin))->runAction($action);
}

function environmentVariableSaved(ActiveCampaign $integration): array
{
    $saved = Formie::$plugin->getIntegrations()->getIntegrationById($integration->id);

    return ['apiKey' => $saved->apiKey, 'apiUrl' => $saved->apiUrl];
}

it('refuses environment variables from users who are not admins', function (): void {
    $integration = environmentVariableIntegration(['apiKey' => 'literal-key', 'apiUrl' => 'https://account.api-us1.com']);

    environmentVariableActor(false, function($request) use ($integration): void {
        foreach (['$CRAFT_DB_PASSWORD', '${CRAFT_DB_PASSWORD}', 'https://example.test/$CRAFT_DB_PASSWORD'] as $value) {
            expect(environmentVariablePost($request, $integration, ['apiKey' => $value, 'apiUrl' => 'https://account.api-us1.com']))->toBeNull()
                ->and(environmentVariableSaved($integration))->toBe(['apiKey' => 'literal-key', 'apiUrl' => 'https://account.api-us1.com']);
        }

        expect(environmentVariablePost($request, null, ['apiKey' => '$CRAFT_DB_PASSWORD', 'apiUrl' => 'https://account.api-us1.com']))->toBeNull();
    });
})->group('security');

it('refuses a connection check with environment variables from users who are not admins', function (): void {
    $integration = environmentVariableIntegration(['apiKey' => 'literal-key', 'apiUrl' => 'https://account.api-us1.com']);

    $response = environmentVariableActor(false, fn($request) => environmentVariablePost($request, $integration, ['apiKey' => '$CRAFT_DB_PASSWORD', 'apiUrl' => 'https://example.test'], 'check-connection'));

    expect($response->getStatusCode())->toBe(400)
        ->and($response->data['message'] ?? '')->toBe('Only admins can use environment variables in integration settings.');
})->group('security');

it('keeps a variable an admin saved when other settings change', function (): void {
    $integration = environmentVariableIntegration(['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://account.api-us1.com']);

    environmentVariableActor(false, function($request) use ($integration): void {
        expect(environmentVariablePost($request, $integration, ['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://account.api-us1.com', 'mapToContact' => true]))->not->toBeNull();
    });
})->group('security');

it('refuses to send a credential from a variable to a new server', function (string $action): void {
    $integration = environmentVariableIntegration(['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://account.api-us1.com']);

    $response = environmentVariableActor(false, fn($request) => environmentVariablePost($request, $integration, ['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://example.test'], $action));

    if ($action === 'save-integration') {
        expect($response)->toBeNull()
            ->and(environmentVariableSaved($integration))->toBe(['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://account.api-us1.com']);
    } else {
        expect($response->data['message'] ?? '')->toBe('Enter the credentials again after changing where this integration connects.');
    }
})->with(['save-integration', 'check-connection'])->group('security');

it('lets users who are not admins change the server for a credential they can see', function (): void {
    $integration = environmentVariableIntegration(['apiKey' => 'literal-key', 'apiUrl' => 'https://account.api-us1.com']);

    environmentVariableActor(false, function($request) use ($integration): void {
        expect(environmentVariablePost($request, $integration, ['apiKey' => 'literal-key', 'apiUrl' => 'https://other.api-us1.com']))->not->toBeNull()
            ->and(environmentVariableSaved($integration))->toBe(['apiKey' => 'literal-key', 'apiUrl' => 'https://other.api-us1.com']);
    });
})->group('security');

it('lets admins use environment variables', function (): void {
    $integration = environmentVariableIntegration(['apiKey' => 'literal-key', 'apiUrl' => 'https://account.api-us1.com']);

    environmentVariableActor(true, function($request) use ($integration): void {
        expect(environmentVariablePost($request, $integration, ['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://example.test']))->not->toBeNull()
            ->and(environmentVariableSaved($integration))->toBe(['apiKey' => '$FORMIE_TEST_AC_KEY', 'apiUrl' => 'https://example.test']);
    });
})->group('security');

it('refuses CAPTCHA settings that use variables or move a masked secret', function (array $settings, string $message): void {
    $provider = SpamProtectionEditor::provider('captchaEu');
    expect($provider)->toBeInstanceOf(CaptchaEu::class);
    $provider->restKey = 'literal-rest-key';
    $provider->publicKey = 'public-key';
    $provider->endPoint = 'https://www.captcha.eu';
    expect(Formie::$plugin->getCaptchaProviders()->saveProvider($provider))->toBeTrue();

    environmentVariableActor(false, function() use ($settings, $message): void {
        expect(fn() => SpamProtectionEditor::postedProvider('captchaEu', ['settings' => $settings]))
            ->toThrow(BadRequestHttpException::class, $message);
    });
})->with([
    'a variable site key' => [['publicKey' => '$CRAFT_DB_PASSWORD', 'endPoint' => 'https://www.captcha.eu'], 'Only admins can use environment variables in integration settings.'],
    'a masked secret sent to a new endpoint' => [['restKey' => SpamProtectionEditor::SECRET_PLACEHOLDER, 'publicKey' => 'public-key', 'endPoint' => 'https://example.test'], 'Enter the credentials again after changing where this integration connects.'],
])->group('security');
