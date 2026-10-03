<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\elements\User;
use craft\fields\PlainText;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\StencilData;
use verbb\formie\services\Permissions;
use verbb\formie\services\Stencils;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

function formUsageAclUser(array $permissions): User
{
    $name = 'formUsageAcl' . bin2hex(random_bytes(6));
    $user = new User([
        'username' => $name,
        'email' => $name . '@example.test',
        'active' => true,
        'pending' => false,
    ]);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        ...$permissions,
    ]))->toBeTrue();

    return $user;
}

function formUsageAclGrant(Form $form, bool $includeUsageTab = true): array
{
    $permissions = [
        Permissions::PERM_ACCESS_FORMS,
        Formie::$plugin->getPermissions()->scopedPermission(
            Permissions::PERM_MANAGE_FORMS,
            Formie::$plugin->getPermissions()->formScope($form),
        ),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ];

    if ($includeUsageTab) {
        $permissions[] = Formie::$plugin->getPermissions()->scopedPermission(
            'formie-showFormUsage',
            Formie::$plugin->getPermissions()->formScope($form),
        );
    }

    return $permissions;
}

function formUsageAclAttachRelations(Form $form, ElementInterface ...$sources): void
{
    $field = new PlainText([
        'name' => 'Form Usage Security Relation',
        'handle' => 'formUsageSecurityRelation' . bin2hex(random_bytes(5)),
    ]);
    expect(Craft::$app->getFields()->saveField($field))->toBeTrue();

    $siteId = Craft::$app->getSites()->getCurrentSite()->id;
    $rows = [];

    foreach ($sources as $index => $source) {
        $rows[] = [
            $field->id,
            $source->id,
            $source->siteId ?: $siteId,
            $form->id,
            $index + 1,
        ];
    }

    Craft::$app->getDb()->createCommand()->batchInsert(Table::RELATIONS, [
        'fieldId',
        'sourceId',
        'sourceSiteId',
        'targetId',
        'sortOrder',
    ], $rows)->execute();
}

function formUsageAclRequest(User $user, int $formId, array $extra = [], bool $includeEntityType = true): array
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $formId, $extra, $includeEntityType): array {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $formId,
            ...($includeEntityType ? ['isStencil' => false] : []),
            ...$extra,
        ]);

        return (new FormsController('forms', Formie::$plugin))->runAction('get-form-usage')->data;
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
}

it('requires management access to the exact form before returning usage', function (): void {
    $allowed = formie()->form(['title' => 'Allowed Usage Form'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $restricted = formie()->form(['title' => 'Restricted Usage Form'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $permissions = formUsageAclGrant($allowed);
    $permissions[] = Permissions::PERM_VIEW_FORMS;
    $user = formUsageAclUser($permissions);

    expect(fn() => formUsageAclRequest($user, (int)$restricted->id))
        ->toThrow(ForbiddenHttpException::class, 'User is not permitted to perform this action');
})->group('security');

it('requires access to the usage tab before returning usage', function (): void {
    $form = formie()->form(['title' => 'Hidden Usage Tab Form'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $user = formUsageAclUser(formUsageAclGrant($form, false));

    expect(fn() => formUsageAclRequest($user, (int)$form->id))
        ->toThrow(ForbiddenHttpException::class, 'User is not permitted to perform this action');
})->group('security');

it('returns only related elements the current user can view', function (): void {
    $form = formie()->form(['title' => 'Authorized Usage Form'])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $user = formUsageAclUser(formUsageAclGrant($form));
    $restricted = User::find()->admin(true)->one();
    formUsageAclAttachRelations($form, $restricted, $user);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $restricted, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'formId' => $form->id,
            'isStencil' => false,
        ]);

        expect(Craft::$app->getElements()->canView($restricted, $identity))->toBeFalse()
            ->and(Craft::$app->getElements()->canView($identity, $identity))->toBeTrue();

        $data = (new FormsController('forms', Formie::$plugin))->runAction('get-form-usage')->data;

        expect($data)->toHaveCount(1)
            ->and($data[0])->toHaveKeys(['element', 'site', 'field', 'level', 'elementType', 'status', 'isRevision', 'isDraft'])
            ->and($data[0]['element']->id)->toBe($identity->id)
            ->and(array_map(fn(array $item): int => (int)$item['element']->id, $data))->not->toContain((int)$restricted->id);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->group('security');

it('keeps stencil usage isolated when a form has the same id', function (): void {
    $form = formie()->form(['title' => 'Colliding Real Form'])->create();
    $restricted = User::find()->admin(true)->one();
    formUsageAclAttachRelations($form, $restricted);

    expect(Formie::$plugin->getStencils()->getStencilById((int)$form->id))->toBeNull();

    $now = DateTimeHelper::currentUTCDateTime();
    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_STENCILS, [
        'id' => $form->id,
        'name' => 'Colliding Stencil',
        'handle' => 'collidingStencil' . bin2hex(random_bytes(5)),
        'scope' => Stencils::SCOPE_SITE,
        'data' => Json::encode((new StencilData())->getSerializedData()),
        'dateCreated' => Db::prepareDateForDb($now),
        'dateUpdated' => Db::prepareDateForDb($now),
        'uid' => Craft::$app->getSecurity()->generateRandomString(),
    ])->execute();
    Formie::$plugin->set('stencils', new Stencils());

    $user = formUsageAclUser(['formie-accessStencils']);

    expect(formUsageAclRequest($user, (int)$form->id, ['isStencil' => true]))->toBe([]);
})->group('security');

it('rejects ambiguous legacy requests without an entity type', function (): void {
    $now = DateTimeHelper::currentUTCDateTime();
    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_STENCILS, [
        'name' => 'Legacy Stencil',
        'handle' => 'legacyStencil' . bin2hex(random_bytes(5)),
        'scope' => Stencils::SCOPE_SITE,
        'data' => Json::encode((new StencilData())->getSerializedData()),
        'dateCreated' => Db::prepareDateForDb($now),
        'dateUpdated' => Db::prepareDateForDb($now),
        'uid' => Craft::$app->getSecurity()->generateRandomString(),
    ])->execute();
    $stencilId = (int)Craft::$app->getDb()->getLastInsertID();
    Formie::$plugin->set('stencils', new Stencils());

    $user = formUsageAclUser([Permissions::PERM_ACCESS_FORMS]);

    expect(fn() => formUsageAclRequest($user, $stencilId, [], false))
        ->toThrow(BadRequestHttpException::class)
        ->and(fn() => formUsageAclRequest($user, $stencilId + 1000000, [], false))
        ->toThrow(BadRequestHttpException::class);
})->group('security');

it('returns a controlled not-found error for an unknown form', function (): void {
    $user = formUsageAclUser([Permissions::PERM_ACCESS_FORMS]);

    expect(fn() => formUsageAclRequest($user, 999999999))
        ->toThrow(NotFoundHttpException::class, 'Form not found');
})->group('security');
