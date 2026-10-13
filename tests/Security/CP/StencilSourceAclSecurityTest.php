<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

function stencilSourceUser(array $permissions): User
{
    $name = 'stencilSource' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS,
        ...$permissions,
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();
    return $user;
}

function stencilSourceRequest(User $user, bool $cp, array $body, array $query, callable $callback): void
{
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $cp, $body, $query, $callback): void {
        $request->setIsCpRequest($cp);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setQueryParams($query);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'title' => 'Copied stencil',
            'handle' => 'copiedStencil' . bin2hex(random_bytes(6)),
            ...$body,
        ]);
        $callback(new FormsController('forms', Formie::$plugin));
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
}

it('denies copying a viewed form before a posted group can grant management', function (bool $cp, bool $querySource): void {
    $allowedGroup = new FormGroup(['name' => 'Allowed', 'handle' => 'allowed' . bin2hex(random_bytes(5))]);
    $restrictedGroup = new FormGroup(['name' => 'Restricted', 'handle' => 'restricted' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($allowedGroup))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($restrictedGroup))->toBeTrue();
    $allowed = formie()->form(['groupId' => $allowedGroup->id])->create();
    $source = formie()->form(['title' => 'Restricted source', 'groupId' => $restrictedGroup->id])->singleLineTextField('privateSetting')->create();
    $user = stencilSourceUser([
        'formie-accessStencils', Permissions::PERM_VIEW_FORMS,
        Formie::$plugin->getPermissions()->scopedPermission(Permissions::PERM_MANAGE_FORMS, Formie::$plugin->getPermissions()->groupScope($allowedGroup->handle)),
    ]);
    $before = (int)(new Query())->from(Table::FORMIE_STENCILS)->count();
    $body = ['id' => $querySource ? $allowed->id : $source->id, 'groupId' => $allowedGroup->id, 'siteId' => $source->siteId];
    $query = $querySource ? ['id' => (string)$source->id, 'siteId' => (string)$source->siteId] : [];

    stencilSourceRequest($user, $cp, $body, $query, function ($controller): void {
        expect(fn() => $controller->runAction('save-as-stencil'))->toThrow(ForbiddenHttpException::class);
    });

    expect((int)(new Query())->from(Table::FORMIE_STENCILS)->count())->toBe($before);
    $reloaded = Form::find()->id($source->id)->siteId($source->siteId)->one();
    expect($reloaded->title)->toBe('Restricted source')->and((int)$reloaded->groupId)->toBe((int)$restrictedGroup->id);
})->with([false, true])->with([false, true])->group('security');

it('denies a stencil-only user copying a source through the site action', function (): void {
    $source = formie()->form()->singleLineTextField('privateSetting')->create();
    $user = stencilSourceUser(['formie-accessStencils']);
    $before = (int)(new Query())->from(Table::FORMIE_STENCILS)->count();
    stencilSourceRequest($user, false, ['id' => $source->id], [], function ($controller): void {
        expect(fn() => $controller->runAction('save-as-stencil'))->toThrow(ForbiddenHttpException::class);
    });
    expect((int)(new Query())->from(Table::FORMIE_STENCILS)->count())->toBe($before);
})->group('security');

it('preserves authorized stencil copies and leaves the source unchanged', function (bool $cp, string $scope): void {
    $source = formie()->form(['title' => 'Source configuration'])
        ->settings(['usePerFormPermissions' => true, 'submitActionMessage' => 'Original success', 'integrations' => ['unavailableProvider' => ['enabled' => true, 'attributeMapping' => ['name' => 'Existing mapping']]]])
        ->singleLineTextField('name', ['label' => 'Source name'])
        ->groupField('details', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'nested', 'label' => 'Nested name',
        ]]]]])->create();
    $permission = match ($scope) {
        'global' => Permissions::PERM_MANAGE_FORMS,
        'form' => Permissions::PERM_MANAGE_FORMS . ':' . $source->uid,
        'group' => Formie::$plugin->getPermissions()->scopedPermission(Permissions::PERM_MANAGE_FORMS, Formie::$plugin->getPermissions()->groupScope(Permissions::GROUP_UNGROUPED)),
    };
    $user = stencilSourceUser(['formie-accessStencils', Permissions::PERM_VIEW_FORMS, $permission]);
    $handle = 'authorizedCopy' . bin2hex(random_bytes(6));
    $beforeLayout = Form::find()->id($source->id)->siteId($source->siteId)->one()->getFormLayout()->getFormBuilderConfig();
    $beforeSettings = (new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $source->id])->scalar();
    $secondarySite = null;
    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        if ((int)$site->id !== (int)$source->siteId) {
            $secondarySite = $site;
            break;
        }
    }
    if ($secondarySite) {
        Formie::$plugin->getFormSiteOverrides()->saveTranslationBundle((int)$source->id, (int)$secondarySite->id, [
            'fieldOverrides' => [(string)$source->getFieldByHandle('name')->definitionId => ['label' => 'Translated name']],
        ]);
    }
    stencilSourceRequest($user, $cp, [
        'id' => (string)$source->id, 'siteId' => (string)$source->siteId, 'handle' => $handle,
        'settings' => ['submitActionMessage' => 'Unsaved success'],
        'notifications' => [['name' => 'Copied notification', 'handle' => 'copiedNotification', 'subject' => 'Subject', 'to' => 'admin@example.test']],
    ], [], function ($controller): void {
        $response = $controller->runAction('save-as-stencil');
        expect($response->data['success'])->toBeTrue()
            ->and($response->data['config'])->not->toBeEmpty()
            ->and($response->data['notifications'])->not->toBeEmpty();
    });
    $stencil = Formie::$plugin->getStencils()->getStencilByHandle($handle);
    expect($stencil)->not->toBeNull()
        ->and($stencil->data->settings->submitActionMessage)->toBe('<p>Unsaved success</p>')
        ->and($stencil->data->notifications[0]->subject)->toBe('Subject')
        ->and($stencil->data->pages[0]->rows[0]->fields)->not->toBeEmpty();
    expect($stencil->data->settings->integrations['unavailableProvider']['attributeMapping']['name'] ?? null)->toBe(['kind' => 'literal', 'value' => 'Existing mapping']);
    if ($secondarySite) {
        expect($stencil->data->translations[$secondarySite->uid]['fieldOverrides'][$source->getFieldByHandle('name')->reference]['label'] ?? null)->toBe('Translated name');
    }
    $serialized = json_encode($stencil->data->getSerializedData());
    expect($serialized)->toContain('Nested name')->toContain('Source name');
    $reloaded = Form::find()->id($source->id)->siteId($source->siteId)->one();
    expect($reloaded->title)->toBe('Source configuration')
        ->and((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $source->id])->scalar())->toBe($beforeSettings)
        ->and($reloaded->getFormLayout()->getFormBuilderConfig())->toBe($beforeLayout);
})->with([false, true])->with(['global', 'form', 'group'])->group('security');

it('preserves source-free stencil creation', function (bool $cp): void {
    $user = stencilSourceUser(['formie-accessStencils']);
    $handle = 'newStencil' . bin2hex(random_bytes(6));
    stencilSourceRequest($user, $cp, ['handle' => $handle], [], function ($controller): void {
        expect($controller->runAction('save-as-stencil')->data['success'])->toBeTrue();
    });
    expect(Formie::$plugin->getStencils()->getStencilByHandle($handle))->not->toBeNull();
})->with([false, true])->group('security');

it('still requires stencil permission even for a source manager', function (): void {
    $source = formie()->form()->create();
    $user = stencilSourceUser([Permissions::PERM_MANAGE_FORMS]);
    stencilSourceRequest($user, false, ['id' => $source->id], [], function ($controller): void {
        expect(fn() => $controller->runAction('save-as-stencil'))->toThrow(ForbiddenHttpException::class);
    });
})->group('security');

it('rejects missing source forms before returning builder data', function (): void {
    $user = stencilSourceUser(['formie-accessStencils', Permissions::PERM_MANAGE_FORMS]);
    $before = (int)(new Query())->from(Table::FORMIE_STENCILS)->count();
    stencilSourceRequest($user, false, ['id' => 999999999], [], function ($controller): void {
        expect(fn() => $controller->runAction('save-as-stencil'))->toThrow(NotFoundHttpException::class);
    });
    expect((int)(new Query())->from(Table::FORMIE_STENCILS)->count())->toBe($before);
})->group('security');
