<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormTemplate;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

function templateFieldsAclFixture(): array
{
    $template = new FormTemplate([
        'name' => 'Template Fields ACL',
        'handle' => 'templateFieldsAcl' . bin2hex(random_bytes(6)),
    ]);
    $alternateTemplate = new FormTemplate([
        'name' => 'Alternate Template Fields ACL',
        'handle' => 'alternateTemplateFieldsAcl' . bin2hex(random_bytes(6)),
    ]);

    expect(Formie::$plugin->getFormTemplates()->saveTemplate($template))->toBeTrue();
    expect(Formie::$plugin->getFormTemplates()->saveTemplate($alternateTemplate))->toBeTrue();

    $allowed = formie()->form([
        'title' => 'Allowed Template Fields',
        'templateId' => $template->id,
    ])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $restricted = formie()->form([
        'title' => 'Restricted Template Fields',
        'templateId' => $template->id,
    ])
        ->settings(['usePerFormPermissions' => true])
        ->create();
    $name = 'templateFieldsAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_MANAGE_FORMS . ':' . $allowed->uid,
        Formie::$plugin->getPermissions()->scopedPermission(
            Permissions::PERM_VIEW_FORMS,
            Formie::$plugin->getPermissions()->groupScope(Permissions::GROUP_UNGROUPED),
        ),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    expect($allowed->templateId)->not->toBeNull()
        ->and($restricted->templateId)->not->toBeNull();

    return compact('allowed', 'restricted', 'user', 'alternateTemplate');
}

it('restricts template field rendering to forms the user can manage', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted, 'user' => $user] = templateFieldsAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $restricted, $user): void {
        $request->setIsCpRequest(true);
        $identity = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Craft::$app->set('sites', new \craft\services\Sites());

        expect(Formie::$plugin->getPermissions()->canViewForm($identity, $restricted))->toBeTrue()
            ->and(Formie::$plugin->getPermissions()->canManageForm($identity, $restricted))->toBeFalse()
            ->and(Formie::$plugin->getPermissions()->getAccessibleFormIds($identity))->toContain($restricted->id)
            ->and(Form::find()->id($restricted->id)->one())->not->toBeNull();

        $request->setQueryParams([
            'formId' => $restricted->id,
            'templateId' => $restricted->templateId,
        ]);

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout'))
            ->toThrow(ForbiddenHttpException::class);

        $request->setQueryParams([
            'formId' => $allowed->id,
            'templateId' => $allowed->templateId,
        ]);

        expect((new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout'))
            ->toBeInstanceOf(Response::class);
    }, ['method' => 'GET']);
})->group('security');

it('restricts template field saves to forms the user can manage without mutating denied forms', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted, 'user' => $user, 'alternateTemplate' => $alternateTemplate] = templateFieldsAclFixture();
    $restrictedTemplateId = $restricted->templateId;
    $restrictedDateUpdated = $restricted->dateUpdated?->format(DATE_ATOM);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($allowed, $restricted, $user, $alternateTemplate): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $csrf = [$request->csrfParam => $request->getCsrfToken()];

        $request->setBodyParams($csrf + [
            'formId' => $restricted->id,
            'templateId' => $alternateTemplate->id,
            'fields' => [],
        ]);

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toThrow(ForbiddenHttpException::class);

        $request->setBodyParams($csrf + [
            'formId' => $allowed->id,
            'templateId' => $allowed->templateId,
            'fields' => [],
        ]);

        expect((new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toBeInstanceOf(Response::class);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    $reloaded = Formie::$plugin->getForms()->getFormById((int)$restricted->id);

    expect($reloaded->templateId)->toBe($restrictedTemplateId)
        ->and($reloaded->dateUpdated?->format(DATE_ATOM))->toBe($restrictedDateUpdated);
})->group('security');

it('requires a control panel request for template field saves', function (): void {
    ['allowed' => $form] = templateFieldsAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setIsCpRequest(false);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()] + $request->getBodyParams());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'formId' => $form->id,
            'templateId' => $form->templateId,
            'fields' => [],
        ],
    ]);
})->group('security');

it('requires post for template field saves', function (): void {
    ['allowed' => $form] = templateFieldsAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toThrow(MethodNotAllowedHttpException::class, 'Post request required');
    }, [
        'method' => 'GET',
        'queryParams' => [
            'formId' => $form->id,
            'templateId' => $form->templateId,
        ],
    ]);
})->group('security');

it('authorizes the effective query form when query and body parameters disagree', function (): void {
    ['allowed' => $allowed, 'restricted' => $restricted, 'user' => $user] = templateFieldsAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()] + $request->getBodyParams());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'queryParams' => [
            'formId' => $restricted->id,
            'templateId' => $restricted->templateId,
        ],
        'bodyParams' => [
            'formId' => $allowed->id,
            'templateId' => $allowed->templateId,
            'fields' => [],
        ],
    ]);
})->group('security');

it('preserves template field not-found responses', function (): void {
    ['allowed' => $form] = templateFieldsAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout'))
            ->toThrow(NotFoundHttpException::class, 'Form not found');
    }, [
        'method' => 'GET',
        'queryParams' => ['formId' => 999999999],
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()] + $request->getBodyParams());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toThrow(NotFoundHttpException::class, 'Form not found');
    }, [
        'method' => 'POST',
        'bodyParams' => ['formId' => 999999999, 'templateId' => $form->templateId],
    ]);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()] + $request->getBodyParams());

        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toThrow(NotFoundHttpException::class, 'Template not found');
    }, [
        'method' => 'POST',
        'bodyParams' => ['formId' => $form->id, 'templateId' => 999999999, 'fields' => []],
    ]);
})->group('security');

it('preserves persisted-template fallback and elevated access', function (): void {
    ['restricted' => $form] = templateFieldsAclFixture();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setQueryParams([
            'formId' => $form->id,
            'templateId' => 999999999,
        ]);

        expect((new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout'))
            ->toBeInstanceOf(Response::class);
    }, ['method' => 'GET']);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()] + [
            'formId' => $form->id,
            'templateId' => $form->templateId,
            'fields' => [],
        ]);

        expect((new FormsController('forms', Formie::$plugin))->runAction('template-fields-slideout-save'))
            ->toBeInstanceOf(Response::class);
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
})->group('security');
