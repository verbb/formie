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
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

function groupNormalizationCipher(mixed $value): string
{
    return 'formie-secret:v1:' . base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Craft::$app->getConfig()->getGeneral()->securityKey));
}

function groupNormalizationValue(string $encoding, string $uid): mixed
{
    return match ($encoding) {
        'array' => [$uid],
        'json' => Json::encode([$uid]),
        'comma' => $uid . ',',
        'duplicate' => [$uid, $uid],
        'associative' => Json::encode(['selected' => $uid]),
        'legacy-list' => groupNormalizationCipher([$uid]),
        'legacy-entry' => [groupNormalizationCipher($uid)],
        'legacy-json-entry' => Json::encode([groupNormalizationCipher($uid)]),
    };
}

function withGroupNormalizationEditor(callable $callback, bool $authorized = false, bool $custom = false): void
{
    $original = Formie::$plugin->getIntegrations();
    $originalUser = Craft::$app->getUser()->getIdentity();
    $registry = new class extends Integrations {
        public UserIntegration $fixture;
        public function getAllIntegrations(): array { return [$this->fixture]; }
        public function getAllCaptchas(): array { return []; }
    };
    $config = ['name' => 'Group normalization', 'handle' => 'userAccounts', 'enabled' => true];
    $registry->fixture = $custom ? new class($config) extends UserIntegration {} : new UserIntegration($config);
    Formie::$plugin->set('integrations', $registry);

    try {
        $suffix = bin2hex(random_bytes(5));
        $group = new UserGroup(['name' => 'Restricted ' . $suffix, 'handle' => 'restricted' . $suffix]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        $actor = new User(['username' => 'groupEditor' . $suffix, 'email' => 'groupEditor' . $suffix . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, [
            'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_MANAGE_FORMS,
            'formie-showFormIntegrations', 'formie-accessStencils', ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()), ...($authorized ? ['viewUsers', 'editUsers', 'assignUserGroup:' . $group->uid] : []),
        ]))->toBeTrue();
        $form = formie()->form()->singleLineTextField('email')->integrations([
            'userAccounts' => ['enabled' => true, 'groupUids' => []],
        ])->create();
        WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $form, $group, $registry, $callback): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($actor->id)->status(null)->one());
            $callback($request, $form, $group, $registry->fixture);
        }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
    } finally {
        Formie::$plugin->set('integrations', $original);
        Craft::$app->getUser()->setIdentity($originalUser);
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
}

function groupNormalizationPost(Form $form, array $settings): array
{
    return ['id' => $form->id, 'siteId' => $form->siteId, 'title' => $form->title, 'handle' => $form->handle,
        'settings' => ['integrations' => ['userAccounts' => $settings]]];
}

function groupNormalizationStored(Form $form): array
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())['integrations']['userAccounts'];
}

it('rejects forbidden groups through the save action before persistence in every supported representation', function(string $encoding, bool $query): void {
    withGroupNormalizationEditor(function($request, $form, $group) use ($encoding, $query): void {
        $before = groupNormalizationStored($form);
        $post = groupNormalizationPost($form, ['enabled' => true, 'groupUids' => groupNormalizationValue($encoding, $group->uid)]);
        $request->setBodyParams($post);
        if ($query) {
            $request->setQueryParams(['settings' => $post['settings']]);
            $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => []]));
        }
        expect(fn() => (new FormsController('forms', Formie::$plugin))->actionSave())->toThrow(ForbiddenHttpException::class, $group->name);
        expect(groupNormalizationStored($form))->toBe($before);
    });
})->with(['array', 'json', 'comma', 'legacy-list', 'legacy-entry', 'legacy-json-entry'])->with([false, true])->group('security');

it('stores authorized groups in the same representation used by the next runtime', function(string $encoding, bool $custom): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration) use ($encoding): void {
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => groupNormalizationValue($encoding, $group->uid)]));
        $response = (new FormsController('forms', Formie::$plugin))->actionSave();
        expect($response?->data['errors'] ?? [])->toBe([]);
        $settings = groupNormalizationStored($form);
        $expected = match ($encoding) {
            'duplicate' => [$group->uid, $group->uid],
            'associative' => ['selected' => $group->uid],
            default => [$group->uid],
        };
        expect($settings['groupUids'])->toBe($expected)
            ->and(FormIntegration::fromSettings($integration, $settings)->createRuntime()->groupUids)->toBe($expected);
    }, true, $custom);
})->with(['array', 'json', 'comma', 'duplicate', 'associative', 'legacy-list', 'legacy-entry', 'legacy-json-entry'])->with([false, true])->group('security');

it('preserves equivalent retained groups and removal but checks re-enabling', function(string $encoding): void {
    withGroupNormalizationEditor(function($request, $form, $group) use ($encoding): void {
        $form->settings->integrations['userAccounts'] = ['enabled' => true, 'groupUids' => groupNormalizationValue($encoding, $group->uid)];
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        Formie::$plugin->getForms()->invalidateFormCaches();
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => [$group->uid]]));
        expect(Formie::$plugin->getForms()->buildFormFromPost()->settings->integrations['userAccounts']['groupUids'])->toBe([$group->uid]);
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => []]));
        expect(Formie::$plugin->getForms()->buildFormFromPost()->settings->integrations['userAccounts']['groupUids'])->toBe([]);
        $form->settings->integrations['userAccounts']['enabled'] = false;
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        Formie::$plugin->getForms()->invalidateFormCaches();
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => groupNormalizationValue($encoding, $group->uid)]));
        expect(fn() => Formie::$plugin->getForms()->buildFormFromPost())->toThrow(ForbiddenHttpException::class, $group->name);
    });
})->with(['array', 'json', 'comma', 'legacy-list', 'legacy-entry'])->group('security');

it('rejects malformed or unavailable group lists cleanly', function(mixed $value): void {
    withGroupNormalizationEditor(function($request, $form) use ($value): void {
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => $value]));
        expect(fn() => Formie::$plugin->getForms()->buildFormFromPost())->toThrow(BadRequestHttpException::class, 'User group assignments');
    });
})->with([[123], ['123'], ['true'], [[["nested"]]], [[false]], [['']], [['formie-secret:v1:unreadable']]])->group('security');

it('preserves empty lists and omitted inherited group defaults', function(mixed $empty): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration) use ($empty): void {
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => $empty]));
        expect(Formie::$plugin->getForms()->buildFormFromPost()->settings->integrations['userAccounts']['groupUids'])->toBe([]);
        $integration->groupUids = [$group->uid];
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true]));
        $settings = Formie::$plugin->getForms()->buildFormFromPost()->settings->integrations['userAccounts'];
        expect(array_key_exists('groupUids', $settings))->toBeFalse()
            ->and(FormIntegration::fromSettings($integration, $settings)->createRuntime()->groupUids)->toBe([$group->uid]);
    });
})->with([[null], ['null'], ['[]'], [[]]])->group('security');

it('checks encoded groups when hydrating a stencil form or saving a source as a stencil', function(bool $source): void {
    withGroupNormalizationEditor(function($request, $form, $group) use ($source): void {
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => Json::encode([$group->uid])]));
        $build = $source
            ? fn() => (new FormsController('forms', Formie::$plugin))->actionSaveAsStencil()
            : fn() => Formie::$plugin->getForms()->buildStencilFormFromPost();
        expect($build)->toThrow(ForbiddenHttpException::class, $group->name);
    });
})->with([false, true])->group('security');

it('rejects legacy wrappers that could change meaning on another load', function(): void {
    withGroupNormalizationEditor(function($request, $form, $group): void {
        $value = groupNormalizationCipher(groupNormalizationCipher(groupNormalizationCipher($group->uid)));
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => [$value]]));
        expect(fn() => Formie::$plugin->getForms()->buildFormFromPost())->toThrow(BadRequestHttpException::class);
    });
})->group('security');

it('checks disabled User definitions before saving any group representation', function(string $encoding, bool $environment): void {
    $previous = getenv('FORMIE_TEST_USER_INTEGRATION_ENABLED');
    putenv('FORMIE_TEST_USER_INTEGRATION_ENABLED=false');
    try {
        withGroupNormalizationEditor(function($request, $form, $group, $integration) use ($encoding, $environment): void {
            $integration->enabled = $environment ? '$FORMIE_TEST_USER_INTEGRATION_ENABLED' : false;
            $service = Formie::$plugin->getIntegrations();
            expect($service->getFormIntegrationByHandle($integration->handle))->toBeNull()
                ->and($service->getFormIntegrationsForForm($form))->toBe([]);
            $before = groupNormalizationStored($form);
            $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => groupNormalizationValue($encoding, $group->uid)]));
            expect(fn() => (new FormsController('forms', Formie::$plugin))->actionSave())->toThrow(ForbiddenHttpException::class, $group->name);
            expect(groupNormalizationStored($form))->toBe($before);
        });
    } finally {
        putenv($previous === false ? 'FORMIE_TEST_USER_INTEGRATION_ENABLED' : 'FORMIE_TEST_USER_INTEGRATION_ENABLED=' . $previous);
    }
})->with(['array', 'json', 'legacy-entry'])->with([false, true])->group('security');

it('allows authorized preparation of disabled User integrations without enabling them', function(): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration): void {
        $integration->enabled = false;
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => Json::encode([$group->uid])]));
        $response = (new FormsController('forms', Formie::$plugin))->actionSave();
        expect($response?->data['errors'] ?? [])->toBe([]);
        $saved = Craft::$app->getElements()->getElementById($form->id, Form::class, $form->siteId);
        $service = Formie::$plugin->getIntegrations();
        expect(groupNormalizationStored($form)['groupUids'])->toBe([$group->uid])
            ->and($service->getFormIntegrationByHandle($integration->handle))->toBeNull()
            ->and($service->getFormIntegrationsForForm($saved))->toBe([])
            ->and($integration->getEnabled())->toBeFalse();
        $integration->enabled = true;
        expect($service->getFormIntegrationsForForm($saved)[0]->createRuntime()->groupUids)->toBe([$group->uid]);
    }, true, true);
})->group('security');

it('preserves retained groups and removal but checks form enabling on disabled User definitions', function(): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration): void {
        $integration->enabled = false;
        $form->settings->integrations['userAccounts'] = ['enabled' => true, 'groupUids' => [$group->uid]];
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => Json::encode([$group->uid])]));
        expect(Formie::$plugin->getForms()->buildFormFromPost()->settings->integrations['userAccounts']['groupUids'])->toBe([$group->uid]);
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => []]));
        expect(Formie::$plugin->getForms()->buildFormFromPost()->settings->integrations['userAccounts']['groupUids'])->toBe([]);
        $form->settings->integrations['userAccounts']['enabled'] = false;
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => [$group->uid]]));
        expect(fn() => Formie::$plugin->getForms()->buildFormFromPost())->toThrow(ForbiddenHttpException::class, $group->name);
    });
})->group('security');

it('checks event modified and hidden disabled User definitions', function(bool $hidden): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration) use ($hidden): void {
        $integration->enabled = false;
        $service = Formie::$plugin->getIntegrations();
        $replacement = $hidden ? new class extends UserIntegration {
            public static function hasFormSettings(): bool { return false; }
        } : new UserIntegration();
        $replacement->handle = 'resolvedUser';
        $replacement->enabled = false;
        $handler = static function($event) use ($replacement): void { $event->integration = $replacement; };
        $service->on(Integrations::EVENT_MODIFY_FORM_INTEGRATION, $handler);
        try {
            $post = groupNormalizationPost($form, []);
            $post['settings']['integrations'] = ['resolvedUser' => ['enabled' => true, 'groupUids' => [$group->uid]]];
            $request->setBodyParams($post);
            expect(fn() => Formie::$plugin->getForms()->buildFormFromPost())->toThrow(ForbiddenHttpException::class, $group->name);
            expect($service->getFormIntegrationByHandle('resolvedUser'))->toBeNull();
        } finally {
            $service->off(Integrations::EVENT_MODIFY_FORM_INTEGRATION, $handler);
        }
    });
})->with([false, true])->group('security');

it('retains unresolved settings while authorizing disabled User definitions', function(): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration): void {
        $integration->enabled = false;
        $post = groupNormalizationPost($form, ['enabled' => true, 'groupUids' => []]);
        $post['settings']['integrations']['unregisteredUser'] = ['enabled' => true, 'groupUids' => [$group->uid]];
        $request->setBodyParams($post);
        $response = (new FormsController('forms', Formie::$plugin))->actionSave();
        expect($response?->data['errors'] ?? [])->toBe([]);
        $saved = Craft::$app->getElements()->getElementById($form->id, Form::class, $form->siteId);
        expect($saved->settings->integrations['unregisteredUser']['groupUids'])->toBe([$group->uid])
            ->and(Formie::$plugin->getIntegrations()->getFormIntegrationsForForm($saved))->toBe([]);
    });
})->group('security');

it('authorizes the exact runtime winner when extensions produce colliding handles', function(): void {
    withGroupNormalizationEditor(function($request, $form, $group, $integration): void {
        $service = new class extends Integrations {
            public array $definitions = [];
            public function getAllIntegrations(): array { return $this->definitions; }
            public function getAllCaptchas(): array { return []; }
        };
        $other = new \verbb\formie\integrations\helpdesk\Freshdesk(['handle' => 'first', 'enabled' => false]);
        $integration->handle = 'second';
        $integration->enabled = false;
        $caseVariant = new \verbb\formie\integrations\helpdesk\Freshdesk(['handle' => 'USERACCOUNTS', 'enabled' => true]);
        $service->definitions = [$other, $integration, $caseVariant];
        $service->on(Integrations::EVENT_MODIFY_FORM_INTEGRATION, static function($event): void {
            if ($event->integration->handle !== 'USERACCOUNTS') {
                $replacement = clone $event->integration;
                $replacement->handle = 'userAccounts';
                $event->integration = $replacement;
            }
        });
        $previous = Formie::$plugin->getIntegrations();
        Formie::$plugin->set('integrations', $service);
        try {
            expect($service->getFormIntegrationDefinitionByHandle('userAccounts'))->toBeInstanceOf(UserIntegration::class)
                ->and($service->getFormIntegrationDefinitionByHandle('USERACCOUNTS'))->toBe($caseVariant)
                ->and($service->getFormIntegrationDefinitionByHandle('UserAccounts'))->toBeNull();
            $request->setBodyParams(groupNormalizationPost($form, ['enabled' => true, 'groupUids' => [$group->uid]]));
            expect(fn() => Formie::$plugin->getForms()->buildFormFromPost())->toThrow(ForbiddenHttpException::class, $group->name);
            $form->settings->integrations['userAccounts'] = ['enabled' => true, 'groupUids' => []];
            expect($service->getFormIntegrationsForForm($form))->toBe([]);
            $integration->enabled = true;
            expect($service->getFormIntegrationsForForm($form)[0]->integration)->toBeInstanceOf(UserIntegration::class);
        } finally {
            Formie::$plugin->set('integrations', $previous);
        }
    });
})->group('security');
