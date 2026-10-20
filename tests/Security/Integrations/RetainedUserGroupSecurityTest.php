<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use craft\models\UserGroup;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\FormsController;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\controllers\StencilsController;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\models\FormIntegration;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;
use verbb\formie\services\Stencils;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;

function retainedGroupPolicy(): array
{
    return ['updateElement' => false, 'updateElementMapping' => ['email' => ['kind' => 'literal', 'value' => 'before@example.test']], 'mergeUserGroups' => false, 'activateUser' => false];
}

function retainedGroupChange(string $setting): mixed
{
    return $setting === 'updateElementMapping' ? ['email' => ['kind' => 'literal', 'value' => 'after@example.test']] : true;
}

function withRetainedGroupEditor(callable $callback, bool $authorized = false, bool $inherited = false, string $scope = Integrations::SCOPE_SITE): void
{
    $suffix = bin2hex(random_bytes(5));
    $group = new UserGroup(['name' => 'Retained group ' . $suffix, 'handle' => 'retainedGroup' . $suffix]);
    expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
    $actor = new User(['username' => 'retainedEditor' . $suffix, 'email' => 'retainedEditor' . $suffix . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, \Tests\Support\PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_MANAGE_FORMS,
        Permissions::PERM_IMPORT_FORMS, Permissions::PERM_ACCESS_INTEGRATIONS, 'formie-accessStencils', 'formie-showFormIntegrations',
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
        ...($authorized ? ['viewUsers', 'editUsers', 'assignUserGroup:' . $group->uid] : []),
    ])))->toBeTrue();
    $integration = new UserIntegration(['name' => 'Retained users', 'handle' => 'retainedUsers' . $suffix,
        'scope' => $scope, 'enabled' => true, 'groupUids' => [$group->uid]]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
    $settings = ['enabled' => true] + retainedGroupPolicy() + ($inherited ? [] : ['groupUids' => [$group->uid]]);
    $form = formie()->form()->singleLineTextField('email')->integrations([$integration->handle => $settings])->create();
    $projectConfig = Craft::$app->getProjectConfig();
    $mutex = Craft::$app->getMutex();
    WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $group, $integration, $form, $callback, $projectConfig, $mutex): void {
        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->set('mutex', $mutex);
        $request->setIsCpRequest(true);
        $actor = User::find()->id($actor->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($actor);
        $callback($request, $form, $integration, $group, $actor);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
    Formie::$plugin->getForms()->invalidateFormCaches();
}

function retainedGroupStored($form): array
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())['integrations'];
}

function retainedGroupSave($request, $form, $integration, $actor, array $settings, string $route): mixed
{
    if ($route === 'import') {
        $data = ImportExportHelper::generateFormExport($form);
        $data['settings'] = Json::decodeIfJson($data['settings']);
        $data['settings']['integrations'][$integration->handle] = $settings;
        return ImportExportHelper::importForUser($data, $form, $actor);
    }
    $post = ['id' => $form->id, 'siteId' => $form->siteId, 'title' => $form->title, 'handle' => $form->handle,
        'settings' => ['integrations' => [$integration->handle => $settings]]];
    $request->setBodyParams($post);
    return (new FormsController('forms', Formie::$plugin))->actionSave();
}

it('requires retained group rights for each protected form policy change', function(string $setting, string $route, bool $inherited): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($setting, $route): void {
        $before = retainedGroupStored($form);
        $settings = $before[$integration->handle];
        $settings[$setting] = retainedGroupChange($setting);
        expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, $route))
            ->toThrow(ForbiddenHttpException::class, $group->name);
        expect(retainedGroupStored($form))->toBe($before);
    }, false, $inherited);
})->with(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser'])->with(['form', 'import'])->with([false, true])->group('security');

it('allows authorized changes without materializing inherited groups', function(string $setting, bool $inherited): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($setting, $inherited): void {
        $settings = retainedGroupStored($form)[$integration->handle];
        $settings[$setting] = retainedGroupChange($setting);
        $result = retainedGroupSave($request, $form, $integration, $actor, $settings, 'form');
        expect($result?->data['errors'] ?? [])->toBe([]);
        $stored = retainedGroupStored($form)[$integration->handle];
        expect(array_key_exists('groupUids', $stored))->toBe(!$inherited)
            ->and(FormIntegration::fromSettings($integration, $stored)->createRuntime()->groupUids)->toBe([$group->uid]);
    }, true, $inherited);
})->with(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser'])->with([false, true])->group('security');

it('permits equivalent policy encodings unrelated edits and explicit group removal', function(string $operation): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($operation): void {
        $settings = retainedGroupStored($form)[$integration->handle];
        if ($operation === 'equivalent') {
            $settings['updateElement'] = '0';
            $settings['mergeUserGroups'] = 0;
            $settings['activateUser'] = 'false';
            $settings['updateElementMapping'] = Json::encode(['email' => ['type' => 'custom', 'value' => 'before@example.test']]);
        } elseif ($operation === 'remove') {
            $settings['groupUids'] = [];
            $settings['activateUser'] = true;
        } else {
            $settings['sendActivationEmail'] = false;
        }
        retainedGroupSave($request, $form, $integration, $actor, $settings, 'import');
        $stored = retainedGroupStored($form)[$integration->handle];
        expect(FormIntegration::fromSettings($integration, $stored)->createRuntime()->groupUids)->toBe($operation === 'remove' ? [] : [$group->uid]);
    }, false, true);
})->with(['equivalent', 'unrelated', 'remove'])->group('security');

it('checks protected global policy changes on retained groups', function(string $setting, bool $authorized, string $scope): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group) use ($setting, $authorized): void {
        $before = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one();
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(), 'id' => $integration->id,
            'name' => $integration->name, 'handle' => $integration->handle, 'type' => UserIntegration::class, 'enabled' => true,
            'types' => [UserIntegration::class => [$setting => retainedGroupChange($setting)]],
        ]);
        $run = fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
        if ($authorized) {
            expect($run())->not->toBeNull();
        } else {
            expect($run)->toThrow(ForbiddenHttpException::class, $group->name);
            expect((new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one())->toBe($before);
        }
    }, $authorized, false, $scope);
})->with(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser'])->with([false, true])->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->group('security');

it('checks protected policy changes in stencils with inherited groups', function(string $setting): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group) use ($setting): void {
        $stencil = new Stencil(['name' => 'Retained stencil', 'handle' => 'retainedStencil' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
        $stencil->data->populateFormData($form);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
        $settings = $stencil->data->settings->integrations[$integration->handle];
        $settings[$setting] = retainedGroupChange($setting);
        $before = (new Query())->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->one();
        $request->setBodyParams(['stencilId' => $stencil->id, 'title' => $stencil->name, 'handle' => $stencil->handle,
            'settings' => ['integrations' => [$integration->handle => $settings]]]);
        expect(fn() => (new StencilsController('stencils', Formie::$plugin))->actionSave())->toThrow(ForbiddenHttpException::class, $group->name);
        expect((new Query())->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->one())->toBe($before);
    }, false, true);
})->with(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser'])->group('security');

it('preserves an unchanged nondefault stencil policy with inherited groups', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration): void {
        $stencil = new Stencil(['name' => 'Unchanged stencil', 'handle' => 'unchangedStencil' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
        $stencil->data->populateFormData($form);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
        $settings = $stencil->data->settings->integrations[$integration->handle];
        $request->setBodyParams(['stencilId' => $stencil->id, 'title' => $stencil->name, 'handle' => $stencil->handle,
            'settings' => ['integrations' => [$integration->handle => $settings]]]);
        $result = (new StencilsController('stencils', Formie::$plugin))->actionSave();
        expect($result?->data['errors'] ?? [])->toBe([]);
        $data = Json::decodeIfJson((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->scalar());
        expect($data['settings']['integrations'][$integration->handle])->not->toHaveKey('groupUids')
            ->and($data['settings']['integrations'][$integration->handle]['updateElementMapping'])->toBe(retainedGroupPolicy()['updateElementMapping']);
    }, false, true);
})->group('security');

it('checks global changes inherited by bindings with their own groups', function(string $setting, string $destination, string $operation): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group) use ($setting, $destination, $operation): void {
        $integration->groupUids = [];
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
        if ($operation !== 'override') {
            unset($form->settings->integrations[$integration->handle][$setting]);
        }
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        if ($destination === 'stencil') {
            $stencil = new Stencil(['name' => 'Inherited policy', 'handle' => 'inheritedPolicy' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
            $stencil->data->populateFormData($form);
            expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
            $form->settings->integrations = [];
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        }
        $before = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one();
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(), 'id' => $integration->id,
            'name' => $integration->name, 'handle' => $integration->handle, 'type' => UserIntegration::class, 'enabled' => true,
            'types' => [UserIntegration::class => [$setting => retainedGroupChange($setting)]],
        ]);
        $run = fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
        if ($operation === 'deny') {
            expect($run)->toThrow(ForbiddenHttpException::class, $group->name);
            expect((new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one())->toBe($before);
        } else {
            expect($run())->not->toBeNull();
        }
    }, $operation === 'authorize');
})->with(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser'])->with(['form', 'stencil'])->with(['deny', 'authorize', 'override'])->group('security');

function retainedGroupCipher(mixed $value): string
{
    return 'formie-secret:v1:' . base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Craft::$app->getConfig()->getGeneral()->securityKey));
}

it('checks and persists the decoded meaning of legacy policy values', function(string $representation, bool $authorized): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($representation, $authorized): void {
        $settings = retainedGroupStored($form)[$integration->handle];
        if ($representation === 'boolean') {
            $settings['updateElement'] = retainedGroupCipher(true);
        } else {
            $settings['updateElementMapping'] = Json::encode(['email' => ['kind' => 'literal', 'value' => retainedGroupCipher('after@example.test')]]);
        }
        $run = fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form');
        if ($authorized) {
            expect($run()?->data['errors'] ?? [])->toBe([]);
            $stored = retainedGroupStored($form)[$integration->handle];
            expect($representation === 'boolean' ? $stored['updateElement'] : $stored['updateElementMapping']['email']['value'])
                ->toBe($representation === 'boolean' ? true : 'after@example.test');
        } else {
            $before = retainedGroupStored($form);
            expect($run)->toThrow(ForbiddenHttpException::class, $group->name);
            expect(retainedGroupStored($form))->toBe($before);
        }
    }, $authorized, true);
})->with(['boolean', 'json-mapping'])->with([false, true])->group('security');

it('rejects unavailable policy ciphertext before typecasting', function(string $representation): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($representation): void {
        $settings = retainedGroupStored($form)[$integration->handle];
        $value = 'formie-secret:v1:unavailable';
        $settings['updateElement'] = $representation === 'nested' ? retainedGroupCipher($value) : $value;
        $before = retainedGroupStored($form);
        expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form'))->toThrow(BadRequestHttpException::class);
        expect(retainedGroupStored($form))->toBe($before);
    });
})->with(['unavailable', 'nested'])->group('security');

it('keeps omitted policy keys inherited during unrelated form edits', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor): void {
        $form->settings->integrations[$integration->handle] = ['enabled' => true];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        retainedGroupSave($request, $form, $integration, $actor, ['enabled' => true, 'sendActivationEmail' => false], 'form');
        $stored = retainedGroupStored($form)[$integration->handle];
        expect(array_intersect_key($stored, retainedGroupPolicy() + ['groupUids' => []]))->toBe([])
            ->and(FormIntegration::fromSettings($integration, $stored)->createRuntime()->groupUids)->toBe([$group->uid]);
    }, false, true);
})->group('security');

it('checks removing an override when that changes the inherited policy', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor): void {
        $integration->activateUser = true;
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
        $settings = retainedGroupStored($form)[$integration->handle];
        unset($settings['activateUser']);
        expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form'))->toThrow(ForbiddenHttpException::class, $group->name);
    }, false, true);
})->group('security');

it('allows authorized stencil policy edits with inherited groups', function(string $setting): void {
    withRetainedGroupEditor(function($request, $form, $integration) use ($setting): void {
        $stencil = new Stencil(['name' => 'Authorized stencil', 'handle' => 'authorizedStencil' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
        $stencil->data->populateFormData($form);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
        $settings = $stencil->data->settings->integrations[$integration->handle];
        $settings[$setting] = retainedGroupChange($setting);
        $request->setBodyParams(['stencilId' => $stencil->id, 'title' => $stencil->name, 'handle' => $stencil->handle,
            'settings' => ['integrations' => [$integration->handle => $settings]]]);
        $result = (new StencilsController('stencils', Formie::$plugin))->actionSave();
        expect($result?->data['errors'] ?? [])->toBe([]);
        $data = Json::decodeIfJson((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->scalar());
        expect($data['settings']['integrations'][$integration->handle])->not->toHaveKey('groupUids')
            ->and($data['settings']['integrations'][$integration->handle][$setting])->toBe(retainedGroupChange($setting));
    }, true, true);
})->with(['updateElement', 'updateElementMapping', 'mergeUserGroups', 'activateUser'])->group('security');

it('checks inherited policy in legacy encoded stencil data', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group): void {
        $integration->groupUids = [];
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
        unset($form->settings->integrations[$integration->handle]['activateUser']);
        $stencil = new Stencil(['name' => 'Legacy inherited policy', 'handle' => 'legacyPolicy' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
        $stencil->data->populateFormData($form);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
        $data = $stencil->data->getSerializedData();
        $data['settings'] = Json::encode($data['settings']);
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, [
            'data' => new \yii\db\JsonExpression(Json::encode($data)),
        ], ['id' => $stencil->id])->execute();
        $raw = (new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->scalar();
        expect((new Stencil(['data' => $raw]))->data->settings->integrations[$integration->handle]['groupUids'])->toBe([$group->uid]);
        $form->settings->integrations = [];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(), 'id' => $integration->id,
            'name' => $integration->name, 'handle' => $integration->handle, 'type' => UserIntegration::class, 'enabled' => true,
            'types' => [UserIntegration::class => ['activateUser' => true]],
        ]);
        expect(fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))
            ->toThrow(ForbiddenHttpException::class, $group->name);
    });
})->group('security');

it('previews unchanged inherited stencil policy and still rejects changed policy', function(bool $changed): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group) use ($changed): void {
        $stencil = new Stencil(['name' => 'Preview policy', 'handle' => 'previewPolicy' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
        $stencil->data->populateFormData($form);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
        $settings = $stencil->data->settings->integrations[$integration->handle];
        if ($changed) {
            $settings['activateUser'] = true;
        }
        $before = (new Query())->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->one();
        $body = ['entityType' => 'stencil', 'stencilId' => $stencil->id, 'title' => $stencil->name, 'handle' => $stencil->handle,
            'settings' => ['integrations' => [$integration->handle => $settings]]];
        $request->setBodyParams(['sentinel' => true]);
        $run = fn() => Formie::$plugin->getFormPreview()->buildFormFromBodyParams($body);
        if ($changed) {
            expect($run)->toThrow(ForbiddenHttpException::class, $group->name);
        } else {
            expect($run()->settings->integrations[$integration->handle])->not->toHaveKey('groupUids');
        }
        expect($request->getBodyParams())->toBe(['sentinel' => true])
            ->and((new Query())->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->one())->toBe($before);
    }, false, true);
})->with([false, true])->group('security');

it('preserves inactive legacy matching slots instead of activating them on save', function(bool $activate): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($activate): void {
        $form->settings->integrations[$integration->handle]['updateElement'] = true;
        $form->settings->integrations[$integration->handle]['updateElementMapping'] = ['email' => ['kind' => 'literal', 'type' => 'none', 'value' => 'before@example.test']];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $before = retainedGroupStored($form);
        expect($before[$integration->handle]['updateElementMapping']['email']['type'])->toBe('none');
        $runtime = FormIntegration::fromSettings($integration, $before[$integration->handle])->createRuntime();
        $submission = new \verbb\formie\elements\Submission();
        $submission->setForm($form);
        expect($runtime->getFieldMappingValues($submission, $runtime->updateElementMapping))->toBe([]);
        $settings = $before[$integration->handle];
        if ($activate) {
            unset($settings['updateElementMapping']['email']['type']);
            expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form'))->toThrow(ForbiddenHttpException::class, $group->name);
            expect(retainedGroupStored($form))->toBe($before);
        } else {
            retainedGroupSave($request, $form, $integration, $actor, $settings, 'form');
            $stored = retainedGroupStored($form)[$integration->handle];
            $runtime = FormIntegration::fromSettings($integration, $stored)->createRuntime();
            expect($runtime->getFieldMappingValues($submission, $runtime->updateElementMapping))->toBe([]);
        }
    }, false, true);
})->with([false, true])->group('security');


it('rejects newly supplied unresolved groups before form or import persistence', function(string $route, string $encoding, bool $enabled): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($route, $encoding, $enabled): void {
        $missing = \craft\helpers\StringHelper::UUID();
        $integration->enabled = $enabled;
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
        $before = retainedGroupStored($form);
        $settings = $before[$integration->handle];
        $settings['groupUids'] = match ($encoding) {
            'json' => Json::encode([$missing]),
            'legacy' => ['formie-secret:v1:' . base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($missing), Craft::$app->getConfig()->getGeneral()->securityKey))],
            default => [$missing],
        };
        expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, $route))
            ->toThrow(BadRequestHttpException::class, 'Create or restore the user group');
        expect(retainedGroupStored($form))->toBe($before);
    });
})->with(['form', 'import'])->with(['array', 'json', 'legacy'])->with([false, true])->group('security');

it('rejects unresolved groups on new imports without creating portable dependencies', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor): void {
        $data = ImportExportHelper::generateFormExport($form);
        $data['settings'] = Json::decodeIfJson($data['settings']);
        $data['settings']['integrations'][$integration->handle]['groupUids'] = [\craft\helpers\StringHelper::UUID()];
        $handle = 'missingGroupImport' . bin2hex(random_bytes(5));
        $data['handle'] = $handle;
        $data['dependencies'][] = ['kind' => 'group', 'config' => ['name' => 'Uncreated group', 'handle' => $handle]];
        Craft::$app->getProjectConfig()->saveModifiedConfigData();
        Craft::$app->getProjectConfig()->reset();
        $config = Craft::$app->getProjectConfig()->get();
        expect(fn() => ImportExportHelper::importForUser($data, null, $actor))->toThrow(BadRequestHttpException::class, 'Create or restore');
        expect(Formie::$plugin->getForms()->getFormByHandle($handle))->toBeNull()
            ->and(Formie::$plugin->getFormGroups()->getGroupByHandle($handle))->toBeNull()
            ->and(Craft::$app->getProjectConfig()->get())->toBe($config);
    });
})->group('security');

it('preserves unresolved historical form references and removals but checks enablement and policy changes', function(string $operation): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($operation): void {
        $missing = \craft\helpers\StringHelper::UUID();
        $settings = retainedGroupStored($form)[$integration->handle];
        $settings['groupUids'] = [$missing];
        $settings['enabled'] = $operation !== 'enable';
        $form->settings->integrations[$integration->handle] = $settings;
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        Formie::$plugin->getForms()->invalidateFormCaches();
        $before = retainedGroupStored($form);
        if ($operation === 'remove') { $settings['groupUids'] = []; }
        if ($operation === 'policy') { $settings['activateUser'] = true; }
        $settings['enabled'] = true;
        if (in_array($operation, ['enable', 'policy'], true)) {
            expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form'))->toThrow(BadRequestHttpException::class, 'Create or restore');
            expect(retainedGroupStored($form))->toBe($before);
        } else {
            retainedGroupSave($request, $form, $integration, $actor, $settings, 'import');
            expect(retainedGroupStored($form)[$integration->handle]['groupUids'])->toBe($operation === 'remove' ? [] : [$missing]);
        }
    });
})->with(['retain', 'remove', 'enable', 'policy'])->group('security');

it('rejects unresolved groups in stencil edits and copies without changing stored data', function(string $route): void {
    withRetainedGroupEditor(function($request, $form, $integration) use ($route): void {
        $settings = retainedGroupStored($form)[$integration->handle];
        $settings['groupUids'] = [\craft\helpers\StringHelper::UUID()];
        $form->settings->integrations[$integration->handle] = $settings;
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        Formie::$plugin->getForms()->invalidateFormCaches();
        $before = retainedGroupStored($form);
        if ($route === 'copy') {
            $request->setBodyParams(['id' => $form->id, 'siteId' => $form->siteId, 'title' => 'Missing group copy', 'handle' => 'missingCopy' . bin2hex(random_bytes(5))]);
            $run = fn() => (new FormsController('forms', Formie::$plugin))->actionSaveAsStencil();
        } else {
            $stencil = new Stencil(['name' => 'Missing groups', 'handle' => 'missingStencil' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
            $stencil->data->populateFormData($form);
            expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
            $request->setBodyParams(['stencilId' => $stencil->id, 'title' => $stencil->name, 'handle' => $stencil->handle, 'settings' => ['integrations' => [$integration->handle => $settings]]]);
            $run = fn() => (new StencilsController('stencils', Formie::$plugin))->actionSave();
        }
        $stencils = (new Query())->from(Table::FORMIE_STENCILS)->all();
        expect($run)->toThrow(BadRequestHttpException::class, 'Create or restore');
        expect((new Query())->from(Table::FORMIE_STENCILS)->all())->toBe($stencils)->and(retainedGroupStored($form))->toBe($before);
    });
})->with(['edit', 'copy'])->group('security');

it('rejects unresolved global groups in both storage scopes before writes', function(string $scope): void {
    withRetainedGroupEditor(function($request, $form, $integration): void {
        $before = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one();
        $config = Craft::$app->getProjectConfig()->get(Integrations::CONFIG_INTEGRATIONS_KEY);
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken(), 'id' => $integration->id, 'name' => $integration->name, 'handle' => $integration->handle, 'type' => UserIntegration::class, 'enabled' => true,
            'types' => [UserIntegration::class => ['groupUids' => [\craft\helpers\StringHelper::UUID()]]]]);
        expect(fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))->toThrow(BadRequestHttpException::class, 'Create or restore');
        expect((new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one())->toBe($before)
            ->and(Craft::$app->getProjectConfig()->get(Integrations::CONFIG_INTEGRATIONS_KEY))->toBe($config);
    }, false, false, $scope);
})->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->group('security');

it('preserves omitted unresolved inheritance but checks a fresh inherited copy', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor): void {
        $missing = \craft\helpers\StringHelper::UUID();
        $integration->groupUids = [$missing];
        expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
        $settings = retainedGroupStored($form)[$integration->handle];
        retainedGroupSave($request, $form, $integration, $actor, $settings, 'form');
        $stored = retainedGroupStored($form)[$integration->handle];
        expect($stored)->not->toHaveKey('groupUids');
        expect(FormIntegration::fromSettings($integration, $stored)->createRuntime()->groupUids)->toBe([$missing]);
        expect(fn() => Formie::$plugin->getForms()->requireUserGroupCopyAccess($form, $actor))->toThrow(BadRequestHttpException::class, 'Create or restore');
    }, false, true);
})->group('security');

it('requires assignment permission after an unresolved group UID is restored', function(): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor): void {
        $uid = \craft\helpers\StringHelper::UUID();
        $before = retainedGroupStored($form);
        $settings = $before[$integration->handle];
        $settings['groupUids'] = [$uid];
        expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form'))->toThrow(BadRequestHttpException::class);
        $restored = new UserGroup(['uid' => $uid, 'name' => 'Restored group ' . $uid, 'handle' => 'restored' . bin2hex(random_bytes(5))]);
        expect(Craft::$app->getUserGroups()->saveGroup($restored))->toBeTrue(json_encode($restored->getErrors()));
        expect(fn() => retainedGroupSave($request, $form, $integration, $actor, $settings, 'form'))->toThrow(ForbiddenHttpException::class, $restored->name);
        expect(retainedGroupStored($form))->toBe($before);
    });
})->group('security');


it('checks unresolved local groups when global policy changes are inherited', function(bool $stencil): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($stencil): void {
        $settings = retainedGroupStored($form)[$integration->handle];
        $settings['groupUids'] = [\craft\helpers\StringHelper::UUID()];
        unset($settings['activateUser']);
        $form->settings->integrations[$integration->handle] = $settings;
        if ($stencil) {
            $model = new Stencil(['name' => 'Inherited missing group', 'handle' => 'inheritedMissing' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
            $model->data->populateFormData($form);
            expect(Formie::$plugin->getStencils()->saveStencil($model))->toBeTrue();
        } else {
            expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        }
        $changed = clone $integration;
        $changed->activateUser = !$integration->activateUser;
        expect(fn() => Formie::$plugin->getForms()->requireGlobalUserGroupAssignmentAccess($changed, $integration, $actor))
            ->toThrow(BadRequestHttpException::class, 'Create or restore');
    }, true);
})->with([false, true])->group('security');

it('preserves unresolved global references for unchanged settings and removals', function(bool $remove): void {
    withRetainedGroupEditor(function($request, $form, $integration, $group, $actor) use ($remove): void {
        $integration->groupUids = [\craft\helpers\StringHelper::UUID()];
        $changed = clone $integration;
        if ($remove) { $changed->groupUids = []; }
        Formie::$plugin->getForms()->requireGlobalUserGroupAssignmentAccess($changed, $integration, $actor);
        expect($changed->groupUids)->toBe($remove ? [] : $integration->groupUids);
    });
})->with([false, true])->group('security');
