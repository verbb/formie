<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use craft\models\UserGroup;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\models\FormIntegration;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

function withGlobalGroupManager(callable $callback, bool $authorized = false): void
{
    $suffix = bin2hex(random_bytes(5));
    $group = new UserGroup(['name' => 'Global restricted ' . $suffix, 'handle' => 'globalRestricted' . $suffix]);
    expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
    $actor = new User(['username' => 'globalManager' . $suffix, 'email' => 'globalManager' . $suffix . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, \Tests\Support\PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_INTEGRATIONS,
        ...($authorized ? ['viewUsers', 'editUsers', 'assignUserGroup:' . $group->uid] : []),
    ])))->toBeTrue();
    $projectConfig = Craft::$app->getProjectConfig();
    WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $group, $callback, $projectConfig): void {
        Craft::$app->set('projectConfig', $projectConfig);
        $request->setIsCpRequest(true);
        $actor = User::find()->id($actor->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($actor);
        expect($actor->can(Permissions::PERM_ACCESS_INTEGRATIONS))->toBeTrue();
        $callback($request, $group, $actor);
    }, ['method' => 'POST', 'requestUri' => '/admin/actions/formie/integrations/save-integration', 'headers' => ['Accept' => 'application/json']]);
}

function globalGroupIntegration(string $scope, array $groups = [], bool $enabled = true): UserIntegration
{
    $integration = new UserIntegration([
        'name' => 'Global groups', 'handle' => 'globalGroups' . bin2hex(random_bytes(5)),
        'scope' => $scope, 'enabled' => $enabled, 'groupUids' => $groups,
    ]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();
    return $integration;
}

function globalGroupPost($request, $integration, array $settings): void
{
    $request->setBodyParams([
        $request->csrfParam => $request->getCsrfToken(),
        'id' => $integration->id, 'name' => $integration->name, 'handle' => $integration->handle,
        'type' => $integration::class, 'enabled' => $integration->getEnabled(), 'scope' => $integration->scope,
        'types' => [$integration::class => $settings],
    ]);
}

function globalGroupValue(string $encoding, string $uid): mixed
{
    $cipher = fn($value) => 'formie-secret:v1:' . base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Craft::$app->getConfig()->getGeneral()->securityKey));
    return match ($encoding) {
        'array' => [$uid], 'json' => Json::encode([$uid]), 'comma' => $uid . ',',
        'legacy-list' => $cipher([$uid]), 'legacy-entry' => [$cipher($uid)],
    };
}

it('checks global groups before saving new or existing integrations in either scope', function(string $scope, bool $existing, string $encoding): void {
    withGlobalGroupManager(function($request, $group) use ($scope, $existing, $encoding): void {
        $integration = $existing ? globalGroupIntegration($scope) : new UserIntegration([
            'name' => 'New global groups', 'handle' => 'newGlobal' . bin2hex(random_bytes(5)), 'scope' => $scope, 'enabled' => false,
        ]);
        $before = (new Query())->from(Table::FORMIE_INTEGRATIONS)->all();
        $config = Craft::$app->getProjectConfig()->get(Integrations::CONFIG_INTEGRATIONS_KEY);
        globalGroupPost($request, $integration, ['groupUids' => globalGroupValue($encoding, $group->uid)]);
        expect(fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))
            ->toThrow(ForbiddenHttpException::class, $group->name);
        expect((new Query())->from(Table::FORMIE_INTEGRATIONS)->all())->toBe($before)
            ->and(Craft::$app->getProjectConfig()->get(Integrations::CONFIG_INTEGRATIONS_KEY))->toBe($config);
    });
})->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->with([false, true])->with(['array', 'json', 'comma', 'legacy-list', 'legacy-entry'])->group('security');

it('saves authorized global groups and preserves omitted versus empty form inheritance', function(string $scope, string $encoding): void {
    withGlobalGroupManager(function($request, $group) use ($scope, $encoding): void {
        $integration = globalGroupIntegration($scope);
        globalGroupPost($request, $integration, ['groupUids' => globalGroupValue($encoding, $group->uid)]);
        expect((new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))->not->toBeNull();
        $reloaded = Formie::$plugin->getIntegrations()->getIntegrationById((int)$integration->id);
        expect($reloaded->groupUids)->toBe([$group->uid])
            ->and(FormIntegration::fromSettings($reloaded, ['enabled' => true])->createRuntime()->groupUids)->toBe([$group->uid])
            ->and(FormIntegration::fromSettings($reloaded, ['enabled' => true, 'groupUids' => []])->createRuntime()->groupUids)->toBe([]);
        $stored = Json::decodeIfJson((new Query())->select('settings')->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->scalar());
        expect($stored['groupUids'])->toBe([$group->uid]);
    }, true);
})->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->with(['array', 'json', 'legacy-entry'])->group('security');

it('retains or removes global groups but checks global re-enabling', function(string $operation): void {
    withGlobalGroupManager(function($request, $group) use ($operation): void {
        $integration = globalGroupIntegration(Integrations::SCOPE_SITE, [$group->uid], $operation !== 'enable');
        globalGroupPost($request, $integration, $operation === 'remove' ? ['groupUids' => []] : []);
        $post = $request->getBodyParams();
        $post['enabled'] = true;
        $post['name'] = 'Edited global groups';
        $request->setBodyParams($post);
        $run = fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
        if ($operation === 'enable') {
            expect($run)->toThrow(ForbiddenHttpException::class, $group->name);
        } else {
            $run();
            $reloaded = Formie::$plugin->getIntegrations()->getIntegrationById((int)$integration->id);
            expect($reloaded->name)->toBe('Edited global groups')
                ->and($reloaded->groupUids)->toBe($operation === 'remove' ? [] : [$group->uid]);
        }
    });
})->with(['retain', 'remove', 'enable'])->group('security');

it('preserves site editing and project restrictions when admin changes are disabled', function(string $scope): void {
    withGlobalGroupManager(function($request, $group) use ($scope): void {
        $integration = globalGroupIntegration($scope);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        globalGroupPost($request, $integration, ['groupUids' => [$group->uid]]);
        $response = (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
        $reloaded = Formie::$plugin->getIntegrations()->getIntegrationById((int)$integration->id);
        expect($reloaded->groupUids)->toBe($scope === Integrations::SCOPE_SITE ? [$group->uid] : []);
        if ($scope === Integrations::SCOPE_PROJECT) {
            expect($response)->toBeNull();
        }
    }, true);
})->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->group('security');

it('rejects malformed and unavailable global group values before persistence', function(mixed $value): void {
    withGlobalGroupManager(function($request) use ($value): void {
        $integration = globalGroupIntegration(Integrations::SCOPE_SITE);
        globalGroupPost($request, $integration, ['groupUids' => $value]);
        expect(fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))->toThrow(BadRequestHttpException::class);
        expect(Formie::$plugin->getIntegrations()->getIntegrationById((int)$integration->id)->groupUids)->toBe([]);
    });
})->with([[[false]], [[["nested"]]], [['formie-secret:v1:unreadable']]])->group('security');

it('preserves trusted global saves without ambient assignment permission', function(): void {
    withGlobalGroupManager(function($request, $group): void {
        $integration = globalGroupIntegration(Integrations::SCOPE_SITE, [$group->uid]);
        expect(Formie::$plugin->getIntegrations()->getIntegrationById((int)$integration->id)->groupUids)->toBe([$group->uid]);
    });
})->group('security');

it('keeps the assignment baseline tied to the selected integration identity', function(string $scope): void {
    withGlobalGroupManager(function($request, $group) use ($scope): void {
        $source = globalGroupIntegration($scope, [$group->uid]);
        $target = globalGroupIntegration($scope);
        globalGroupPost($request, $source, ['id' => $target->id, 'uid' => $target->uid, 'groupUids' => [$group->uid]]);
        (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
        expect(Formie::$plugin->getIntegrations()->getIntegrationById((int)$source->id)->groupUids)->toBe([$group->uid])
            ->and(Formie::$plugin->getIntegrations()->getIntegrationById((int)$target->id)->groupUids)->toBe([]);
    });
})->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT])->group('security');

it('does not let nested settings move a project integration into editable site scope', function(): void {
    withGlobalGroupManager(function($request, $group): void {
        $integration = globalGroupIntegration(Integrations::SCOPE_PROJECT, [$group->uid]);
        Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;
        $before = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one();
        globalGroupPost($request, $integration, ['scope' => Integrations::SCOPE_SITE]);
        expect((new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))->toBeNull()
            ->and((new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one())->toBe($before);
    });
})->group('security');

it('checks nested enablement and uses an empty baseline for new User definitions', function(bool $new): void {
    withGlobalGroupManager(function($request, $group, $actor) use ($new): void {
        if ($new) {
            $integration = new class(['groupUids' => [$group->uid]]) extends UserIntegration {};
            expect(fn() => Formie::$plugin->getForms()->requireGlobalUserGroupAssignmentAccess($integration, null, $actor))
                ->toThrow(ForbiddenHttpException::class, $group->name);
        } else {
            $integration = globalGroupIntegration(Integrations::SCOPE_SITE, [$group->uid], false);
            globalGroupPost($request, $integration, ['enabled' => true]);
            expect(fn() => (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))
                ->toThrow(ForbiddenHttpException::class, $group->name);
        }
    });
})->with([false, true])->group('security');

it('preserves ordinary non-User global integration saves', function(): void {
    withGlobalGroupManager(function($request): void {
        $integration = new Mailchimp(['name' => 'Other integration', 'handle' => 'otherGlobal' . bin2hex(random_bytes(5)), 'scope' => Integrations::SCOPE_SITE]);
        globalGroupPost($request, $integration, ['apiKey' => 'synthetic-test-api-key']);
        expect((new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration'))->not->toBeNull();
        expect(Formie::$plugin->getIntegrations()->getIntegrationByHandle($integration->handle)->apiKey)->toBe('synthetic-test-api-key');
    });
})->group('security');

it('creates users with authorized inherited groups while preserving an explicit empty override', function(): void {
    withGlobalGroupManager(function($request, $group): void {
        $form = formie()->form()->singleLineTextField('username')->emailField('email')->create();
        $reference = fn($handle) => \verbb\formie\helpers\References::field($form->getFieldByHandle($handle)->reference);
        $integration = globalGroupIntegration(Integrations::SCOPE_SITE);
        globalGroupPost($request, $integration, [
            'groupUids' => [$group->uid], 'sendActivationEmail' => false,
        ]);
        (new IntegrationsController('integrations', Formie::$plugin))->runAction('save-integration');
        $reloaded = Formie::$plugin->getIntegrations()->getIntegrationById((int)$integration->id);
        foreach ([false, true] as $empty) {
            $suffix = 'inherited' . bin2hex(random_bytes(5));
            $submission = formie()->submission($form)->with(['username' => $suffix, 'email' => $suffix . '@example.test'])->save();
            $submission = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
            \Tests\Support\IntegrationTestHelper::primeVariableCacheForSubmission($submission);
            $settings = ['enabled' => true, 'attributeMapping' => ['username' => $reference('username'), 'email' => $reference('email')]] + ($empty ? ['groupUids' => []] : []);
            $runtime = FormIntegration::fromSettings($reloaded, $settings)->createRuntime();
            $result = Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'global-group-' . $suffix, 'synchronous', [
                'triggerEvent' => 'submit', 'authorityType' => \verbb\formie\enums\SubmissionAuthorityType::VISITOR->value,
                'authorityScope' => 'global-group-test', 'actorId' => null,
            ]);
            $this->assertTrue($result->isSuccessful(), Json::encode($result));
            $created = User::find()->email($suffix . '@example.test')->status(null)->one();
            expect($created)->not->toBeNull()
                ->and(array_map(fn($assigned) => $assigned->uid, $created->getGroups()))->toBe($empty ? [] : [$group->uid]);
        }
    }, true);
})->group('security');
