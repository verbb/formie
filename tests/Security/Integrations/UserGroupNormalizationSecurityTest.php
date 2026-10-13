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
