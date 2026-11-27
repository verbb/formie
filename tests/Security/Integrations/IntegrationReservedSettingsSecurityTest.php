<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;

// Posted provider settings only carry settings. Keys such as `scope`, `id` and `uid` belong to the saved
// integration, so a non-admin can't use them to unlock or retarget a project integration.

function reservedSettingsManager(callable $callback): void
{
    $suffix = bin2hex(random_bytes(5));
    $actor = new User(['username' => 'reservedSettings' . $suffix, 'email' => 'reservedSettings' . $suffix . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_INTEGRATIONS,
    ])))->toBeTrue();

    $general = Craft::$app->getConfig()->getGeneral();
    $allowAdminChanges = $general->allowAdminChanges;
    $projectConfig = Craft::$app->getProjectConfig();

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $callback, $projectConfig): void {
            Craft::$app->set('projectConfig', $projectConfig);
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($actor->id)->status(null)->one());
            $callback($request);
        }, ['method' => 'POST', 'requestUri' => '/admin/actions/formie/integrations/save-integration', 'headers' => ['Accept' => 'application/json']]);
    } finally {
        $general->allowAdminChanges = $allowAdminChanges;
    }
}

function reservedSettingsIntegration(string $scope, string $apiKey): Mailchimp
{
    $integration = new Mailchimp(['name' => 'Reserved settings', 'handle' => 'reservedSettings' . bin2hex(random_bytes(5)), 'scope' => $scope, 'apiKey' => $apiKey]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();

    return $integration;
}

function reservedSettingsRow(int $id): array
{
    return (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $id])->one();
}

it('ignores a posted scope that would unlock a project integration', function(): void {
    reservedSettingsManager(function($request): void {
        $integration = reservedSettingsIntegration(Integrations::SCOPE_PROJECT, 'project-api-key');
        $before = reservedSettingsRow($integration->id);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $integration->id, 'name' => $integration->name, 'handle' => $integration->handle,
            'type' => Mailchimp::class, 'enabled' => true,
            'types' => [Mailchimp::class => ['scope' => Integrations::SCOPE_SITE, 'apiKey' => 'replacement-api-key']],
        ]);

        (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');

        expect(reservedSettingsRow($integration->id))->toBe($before);
    });
})->group('security');

it('ignores a posted id or uid that would retarget another integration', function(string $key): void {
    reservedSettingsManager(function($request) use ($key): void {
        $target = reservedSettingsIntegration(Integrations::SCOPE_PROJECT, 'project-api-key');
        $own = reservedSettingsIntegration(Integrations::SCOPE_SITE, 'own-api-key');
        $before = reservedSettingsRow($target->id);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $own->id, 'name' => $own->name, 'handle' => $own->handle,
            'type' => Mailchimp::class, 'enabled' => true,
            'types' => [Mailchimp::class => [$key => $key === 'id' ? $target->id : $target->uid, 'scope' => Integrations::SCOPE_SITE, 'apiKey' => 'replacement-api-key']],
        ]);

        (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');

        expect(reservedSettingsRow($target->id))->toBe($before);
    });
})->with(['id', 'uid'])->group('security');

it('still saves ordinary provider settings', function(): void {
    reservedSettingsManager(function($request): void {
        $integration = reservedSettingsIntegration(Integrations::SCOPE_SITE, 'old-api-key');

        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'id' => $integration->id, 'name' => $integration->name, 'handle' => $integration->handle,
            'type' => Mailchimp::class, 'enabled' => true,
            'types' => [Mailchimp::class => ['apiKey' => 'new-api-key']],
        ]);

        expect((new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))->not->toBeNull()
            ->and(Formie::$plugin->getIntegrations()->getIntegrationByHandle($integration->handle)->apiKey)->toBe('new-api-key');
    });
})->group('security');
