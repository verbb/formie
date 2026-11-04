<?php

declare(strict_types=1);

use craft\elements\User;
use craft\services\UserPermissions;
use craft\web\TemplateResponseBehavior;
use craft\web\TemplateResponseFormatter;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\base\web\twig\Extension as BaseTwigExtension;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

function submissionCreatorUser(array $grants): User
{
    $name = 'creator' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());
    $grants = array_merge(['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS], $grants);

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        $grants[] = 'editSite:' . $site->uid;
    }

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents($grants)))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

function submissionCreatorGroupForm(string $prefix): array
{
    $group = new FormGroup(['name' => $prefix, 'handle' => $prefix . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    return [$group, formie()->form(['title' => $prefix . ' Form', 'groupId' => $group->id])->singleLineTextField('message')->create()];
}

function submissionCreatorContext(User $user, callable $callback, array $options = []): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $callback) {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        Craft::$app->getView()->registerTwigExtension(new BaseTwigExtension());

        return $callback(new SubmissionsController('submissions', Formie::$plugin));
    }, $options + ['method' => 'GET', 'requestUri' => '/admin/formie/submissions']);
}

function submissionCreatorOffers(User $user): array
{
    return submissionCreatorContext($user, function (SubmissionsController $controller): array {
        $variables = $controller->actionIndex()->getBehavior(TemplateResponseBehavior::NAME)->variables;

        return array_map('intval', array_column($variables['editableForms'], 'id'));
    });
}

function submissionCreatorOpen(User $user, Form $form): array
{
    return submissionCreatorContext($user, function (SubmissionsController $controller) use ($form): array {
        $response = $controller->actionEditSubmission($form->handle);
        $behavior = $response->getBehavior(TemplateResponseBehavior::NAME);
        (new TemplateResponseFormatter())->format($response);

        return ['template' => $behavior->template, 'html' => (string)$response->content, 'form' => (int)$behavior->variables['form']->id];
    }, ['requestUri' => '/admin/formie/submissions/' . $form->handle . '/new']);
}

function submissionCreatorSave(User $user, Form $form, array $fields, ?Submission $submission = null): mixed
{
    return submissionCreatorContext($user, fn(SubmissionsController $controller) => $controller->actionSaveAdminSubmission(), [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/submissions/save-admin-submission',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'handle' => $form->handle,
            'submissionId' => $submission?->id,
            'expectedVersion' => $submission?->stateVersion ?? 0,
            'siteId' => $form->siteId,
            'fields' => $fields,
        ],
    ]);
}

function submissionCreatorRows(Form $form): array
{
    return Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->all();
}

it('opens and saves the form the submissions index offers a creator without form access', function (): void {
    $form = formie()->form(['title' => 'Creator Form'])->singleLineTextField('message')->create();
    $user = submissionCreatorUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]);

    expect(submissionCreatorOffers($user))->toContain((int)$form->id);

    $page = submissionCreatorOpen($user, $form);
    expect($page['template'])->toBe('formie/submissions/_edit')
        ->and($page['form'])->toBe((int)$form->id)
        ->and($page['html'])->toContain('Create a new submission')
        ->and($page['html'])->toContain('name="fields[message]"');

    $response = submissionCreatorSave($user, $form, ['message' => 'Created by creator']);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));

    $rows = submissionCreatorRows($form);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->getFieldValue('message'))->toBe('Created by creator');
});

it('does not widen the form index scope for a creator', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $user = submissionCreatorUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]);

    submissionCreatorContext($user, function (SubmissionsController $controller) use ($form): void {
        $controller->actionEditSubmission($form->handle);

        expect(Formie::$plugin->getPermissions()->getAccessibleFormIds(Craft::$app->getUser()->getIdentity()))->toBe([])
            ->and(Form::find()->handle($form->handle)->ids())->toBe([]);
    }, ['requestUri' => '/admin/formie/submissions/' . $form->handle . '/new']);
});

it('limits a group scoped creator to the forms in that group', function (): void {
    [$allowedGroup, $allowed] = submissionCreatorGroupForm('creatorAllowed');
    [, $denied] = submissionCreatorGroupForm('creatorDenied');
    $permissions = Formie::$plugin->getPermissions();
    $scope = $permissions->groupScope($allowedGroup->handle);
    $user = submissionCreatorUser([
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $scope),
        $permissions->scopedPermission(Permissions::PERM_CREATE_SUBMISSIONS, $scope),
    ]);

    $offers = submissionCreatorOffers($user);
    expect($offers)->toContain((int)$allowed->id)
        ->and($offers)->not->toContain((int)$denied->id);

    expect(submissionCreatorOpen($user, $allowed)['template'])->toBe('formie/submissions/_edit');
    expect(fn() => submissionCreatorOpen($user, $denied))->toThrow(HttpException::class);

    $response = submissionCreatorSave($user, $allowed, ['message' => 'Allowed group']);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    expect(submissionCreatorRows($allowed))->toHaveCount(1);

    expect(fn() => submissionCreatorSave($user, $denied, ['message' => 'Denied group']))->toThrow(BadRequestHttpException::class);
    expect(submissionCreatorRows($denied))->toBe([]);
});

it('limits a form scoped creator to that form', function (): void {
    $allowed = formie()->form(['title' => 'Dedicated Allowed'])->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $denied = formie()->form(['title' => 'Dedicated Denied'])->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $permissions = Formie::$plugin->getPermissions();
    $scope = $permissions->formScope($allowed);
    $user = submissionCreatorUser([
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $scope),
        $permissions->scopedPermission(Permissions::PERM_CREATE_SUBMISSIONS, $scope),
    ]);

    $offers = submissionCreatorOffers($user);
    expect($offers)->toContain((int)$allowed->id)
        ->and($offers)->not->toContain((int)$denied->id);

    expect(submissionCreatorOpen($user, $allowed)['template'])->toBe('formie/submissions/_edit');
    expect(fn() => submissionCreatorOpen($user, $denied))->toThrow(HttpException::class);

    $response = submissionCreatorSave($user, $allowed, ['message' => 'Dedicated']);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    expect(submissionCreatorRows($allowed)[0]->getFieldValue('message'))->toBe('Dedicated');

    expect(fn() => submissionCreatorSave($user, $denied, ['message' => 'Denied']))->toThrow(BadRequestHttpException::class);
    expect(submissionCreatorRows($denied))->toBe([]);
});

it('keeps the new submission screen and save closed without a create grant', function (): void {
    $form = formie()->form(['title' => 'No Create Form'])->singleLineTextField('message')->create();
    $user = submissionCreatorUser([Permissions::PERM_VIEW_SUBMISSIONS]);

    expect(submissionCreatorOffers($user))->not->toContain((int)$form->id);

    try {
        submissionCreatorOpen($user, $form);
        $this->fail('The new submission screen opened without a create grant.');
    } catch (HttpException $exception) {
        expect($exception->statusCode)->toBe(404);
    }

    expect(fn() => submissionCreatorSave($user, $form, ['message' => 'Denied']))->toThrow(BadRequestHttpException::class);
    expect(submissionCreatorRows($form))->toBe([]);
});

it('keeps saving closed for a form viewer without a create grant', function (): void {
    $form = formie()->form(['title' => 'Viewer Form'])->singleLineTextField('message')->create();
    $user = submissionCreatorUser([Permissions::PERM_ACCESS_FORMS, Permissions::PERM_VIEW_FORMS]);

    expect(fn() => submissionCreatorSave($user, $form, ['message' => 'Denied']))->toThrow(ForbiddenHttpException::class);
    expect(submissionCreatorRows($form))->toBe([]);
});

it('keeps a form viewer with a create grant working', function (): void {
    $form = formie()->form(['title' => 'Viewer Creator Form'])->singleLineTextField('message')->create();
    $user = submissionCreatorUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS, Permissions::PERM_ACCESS_FORMS, Permissions::PERM_VIEW_FORMS]);

    expect(submissionCreatorOpen($user, $form)['template'])->toBe('formie/submissions/_edit');

    $response = submissionCreatorSave($user, $form, ['message' => 'Viewer created']);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    expect(submissionCreatorRows($form)[0]->getFieldValue('message'))->toBe('Viewer created');
});

it('saves an existing submission for a user whose save grant comes without form access', function (bool $canSave): void {
    $form = formie()->form(['title' => 'Editor Form'])->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => 'Original'])->save();
    $user = submissionCreatorUser($canSave
        ? [Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_SAVE_SUBMISSIONS]
        : [Permissions::PERM_VIEW_SUBMISSIONS]);

    if ($canSave) {
        $response = submissionCreatorSave($user, $form, ['message' => 'Edited'], $submission);
        expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    } else {
        expect(fn() => submissionCreatorSave($user, $form, ['message' => 'Edited'], $submission))->toThrow(BadRequestHttpException::class);
    }

    expect(Submission::find()->id($submission->id)->one()->getFieldValue('message'))->toBe($canSave ? 'Edited' : 'Original');
})->with(['save grant' => true, 'view only' => false]);
