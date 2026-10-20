<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use craft\models\UserGroup;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\models\FormIntegration;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;
use verbb\formie\services\Stencils;
use yii\web\ForbiddenHttpException;

function withStencilGroupCopier(callable $callback, string $encoding = 'array', bool $authorized = false, bool $integrationTab = true, bool $cp = true, bool $globalEnabled = true, bool $bindingEnabled = true): void
{
    $suffix = bin2hex(random_bytes(5));
    $group = new UserGroup(['name' => 'Copy group ' . $suffix, 'handle' => 'copyGroup' . $suffix]);
    expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
    $actor = new User(['username' => 'stencilCopier' . $suffix, 'email' => 'stencilCopier' . $suffix . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, \Tests\Support\PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_MANAGE_FORMS, 'formie-accessStencils',
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
        ...($integrationTab ? ['formie-showFormIntegrations'] : []),
        ...($authorized ? ['viewUsers', 'editUsers', 'assignUserGroup:' . $group->uid] : []),
    ])))->toBeTrue();
    $integration = new UserIntegration(['name' => 'Copied users', 'handle' => 'copiedUsers' . $suffix,
        'scope' => Integrations::SCOPE_SITE, 'enabled' => $globalEnabled, 'groupUids' => [$group->uid]]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
    $cipher = fn($value) => 'formie-secret:v1:' . base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Craft::$app->getConfig()->getGeneral()->securityKey));
    $groups = match ($encoding) {
        'json' => Json::encode([$group->uid]), 'comma' => $group->uid . ',',
        'legacy-list' => $cipher([$group->uid]), 'legacy-entry' => [$cipher($group->uid)],
        'empty' => [], 'null' => null, default => [$group->uid],
    };
    $binding = ['enabled' => $bindingEnabled] + ($encoding === 'inherited' ? [] : ['groupUids' => $groups]);
    $form = formie()->form(['title' => 'Copy source'])->singleLineTextField('email')->integrations([$integration->handle => $binding])->create();
    $projectConfig = Craft::$app->getProjectConfig();
    $mutex = Craft::$app->getMutex();
    WebRequestTestHelper::withWebRequestContext(function($request) use ($callback, $actor, $group, $integration, $form, $projectConfig, $mutex, $cp): void {
        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->set('mutex', $mutex);
        $request->setIsCpRequest($cp);
        Craft::$app->getUser()->setIdentity(User::find()->id($actor->id)->status(null)->one());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken(),
            'id' => $form->id, 'siteId' => $form->siteId, 'title' => 'Copied registration', 'handle' => 'copyResult' . bin2hex(random_bytes(5))]);
        $callback($request, $form, $integration, $group);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
    Formie::$plugin->getForms()->invalidateFormCaches();
}

function copyUserGroupStencil(): mixed
{
    return (new FormsController('forms', Formie::$plugin))->runAction('save-as-stencil');
}

it('requires fresh assignment rights for copied explicit and inherited user groups', function(string $encoding, bool $integrationTab, bool $cp): void {
    withStencilGroupCopier(function($request, $form, $integration, $group): void {
        $before = (new Query())->from(Table::FORMIE_STENCILS)->count();
        $source = (new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();
        $config = Craft::$app->getProjectConfig()->get(Stencils::CONFIG_STENCILS_KEY);
        expect(fn() => copyUserGroupStencil())->toThrow(ForbiddenHttpException::class, $group->name);
        expect((new Query())->from(Table::FORMIE_STENCILS)->count())->toBe($before)
            ->and((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())->toBe($source)
            ->and(Craft::$app->getProjectConfig()->get(Stencils::CONFIG_STENCILS_KEY))->toBe($config);
    }, $encoding, false, $integrationTab, $cp);
})->with(['array', 'json', 'comma', 'legacy-list', 'legacy-entry', 'inherited'])->with([false, true])->with([false, true])->group('security');

it('preserves authorized copies and future global inheritance', function(string $encoding, bool $integrationTab): void {
    withStencilGroupCopier(function($request, $form, $integration, $group) use ($encoding): void {
        $source = (new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();
        $layout = Form::find()->id($form->id)->one()->getFormLayout()->getFormBuilderConfig();
        $post = $request->getBodyParams();
        $post['notifications'] = [['name' => 'Copied notification', 'handle' => 'copiedNotification', 'subject' => 'Copied subject', 'to' => 'admin@example.test']];
        $request->setBodyParams($post);
        expect(copyUserGroupStencil()->data['success'])->toBeTrue();
        $stencil = Formie::$plugin->getStencils()->getStencilByHandle($post['handle']);
        $stored = $stencil->data->settings->integrations[$integration->handle];
        expect(array_key_exists('groupUids', $stored))->toBe($encoding !== 'inherited')
            ->and(FormIntegration::fromSettings($integration, $stored)->createRuntime()->groupUids)->toBe([$group->uid])
            ->and($stencil->data->notifications[0]->subject)->toBe('Copied subject')
            ->and(Json::encode($stencil->data->getSerializedData()))->toContain('email')
            ->and((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())->toBe($source)
            ->and(Form::find()->id($form->id)->one()->getFormLayout()->getFormBuilderConfig())->toBe($layout);
        $integration->groupUids = [];
        expect(FormIntegration::fromSettings($integration, $stored)->createRuntime()->groupUids)->toBe($encoding === 'inherited' ? [] : [$group->uid]);
    }, $encoding, true, $integrationTab);
})->with(['array', 'json', 'legacy-entry', 'inherited'])->with([false, true])->group('security');

it('checks copied groups even when their configured definition is disabled', function(string $encoding): void {
    withStencilGroupCopier(function($request, $form, $integration, $group): void {
        expect(fn() => copyUserGroupStencil())->toThrow(ForbiddenHttpException::class, $group->name);
    }, $encoding, false, false, true, false);
})->with(['array', 'inherited'])->group('security');

it('allows empty group overrides and excludes inactive bindings from copies', function(string $operation): void {
    withStencilGroupCopier(function($request, $form, $integration) use ($operation): void {
        $handle = $request->getBodyParam('handle');
        expect(copyUserGroupStencil()->data['success'])->toBeTrue();
        $bindings = Formie::$plugin->getStencils()->getStencilByHandle($handle)->data->settings->integrations;
        if ($operation === 'disabled') {
            expect($bindings)->not->toHaveKey($integration->handle);
        } else {
            expect(FormIntegration::fromSettings($integration, $bindings[$integration->handle])->createRuntime()->groupUids)->toBe([]);
        }
    }, $operation === 'disabled' ? 'array' : $operation, false, false, true, true, $operation !== 'disabled');
})->with(['empty', 'null', 'disabled'])->group('security');

it('rejects unreadable or malformed copied groups without integration tab access', function(string $value): void {
    withStencilGroupCopier(function($request, $form, $integration) use ($value): void {
        $form->settings->integrations[$integration->handle]['groupUids'] = $value;
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $before = (new Query())->from(Table::FORMIE_STENCILS)->count();
        expect(fn() => copyUserGroupStencil())->toThrow(\yii\web\BadRequestHttpException::class);
        expect((new Query())->from(Table::FORMIE_STENCILS)->count())->toBe($before);
    }, 'array', false, false);
})->with(['formie-secret:v1:unavailable', '[null]'])->group('security');

it('checks copied groups under extension renamed handles', function(): void {
    withStencilGroupCopier(function($request, $form, $integration, $group): void {
        $alias = 'copyAlias' . bin2hex(random_bytes(5));
        $form->settings->integrations = [$alias => ['enabled' => true]];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $handler = function($event) use ($integration, $alias): void {
            if ($event->integration->id === $integration->id) {
                $event->integration = clone $event->integration;
                $event->integration->handle = $alias;
            }
        };
        $service = Formie::$plugin->getIntegrations();
        $service->on(Integrations::EVENT_MODIFY_FORM_INTEGRATION, $handler);
        try {
            expect(fn() => copyUserGroupStencil())->toThrow(ForbiddenHttpException::class, $group->name);
        } finally {
            $service->off(Integrations::EVENT_MODIFY_FORM_INTEGRATION, $handler);
        }
    }, 'inherited', false, false);
})->group('security');

it('uses the effective definition when extension handles collide during copy', function(): void {
    withStencilGroupCopier(function($request, $form, $integration, $group): void {
        $integration->groupUids = [];
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
        $winner = new UserIntegration(['name' => 'Copy winner', 'handle' => 'copyWinner' . bin2hex(random_bytes(5)),
            'scope' => Integrations::SCOPE_SITE, 'sortOrder' => 99999, 'enabled' => true, 'groupUids' => [$group->uid]]);
        expect(Formie::$plugin->getIntegrations()->saveIntegration($winner))->toBeTrue();
        $handler = function($event) use ($integration, $winner): void {
            if ($event->integration->id === $winner->id) {
                $event->integration = clone $event->integration;
                $event->integration->handle = $integration->handle;
            }
        };
        $service = Formie::$plugin->getIntegrations();
        $service->on(Integrations::EVENT_MODIFY_FORM_INTEGRATION, $handler);
        try {
            expect($service->resolveIntegrationDefinitionByHandle($integration->handle)->id)->toBe($winner->id);
            expect(fn() => copyUserGroupStencil())->toThrow(ForbiddenHttpException::class, $group->name);
        } finally {
            $service->off(Integrations::EVENT_MODIFY_FORM_INTEGRATION, $handler);
        }
    }, 'inherited', false, false);
})->group('security');
