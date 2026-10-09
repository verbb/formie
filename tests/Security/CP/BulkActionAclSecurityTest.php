<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\elements\actions\DuplicateForm;
use verbb\formie\elements\actions\SetSubmissionSpam;
use verbb\formie\elements\actions\SetSubmissionStatus;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\services\Permissions;

function bulkActionAclFixture(callable $permissionsForForms): array
{
    $allowedForm = formie()->form(['title' => 'Allowed Bulk Action'])
        ->settings(['usePerFormPermissions' => true])
        ->singleLineTextField('fullName')
        ->create();
    $restrictedForm = formie()->form(['title' => 'Restricted Bulk Action'])
        ->settings(['usePerFormPermissions' => true])
        ->singleLineTextField('fullName')
        ->create();
    $allowedSubmission = formie()->submission($allowedForm)->with(['fullName' => 'Allowed'])->save();
    $restrictedSubmission = formie()->submission($restrictedForm)->with(['fullName' => 'Restricted'])->save();
    $name = 'bulkActionAcl' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        ...$permissionsForForms($allowedForm, $restrictedForm),
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    return compact('allowedForm', 'restrictedForm', 'allowedSubmission', 'restrictedSubmission', 'user');
}

function bulkActionAclIdentity(User $user): User
{
    return User::find()->id($user->id)->status(null)->one();
}

it('checks submission save access before bulk status changes', function (): void {
    $permissionService = Formie::$plugin->getPermissions();
    $fixture = bulkActionAclFixture(fn($allowedForm) => [
        Permissions::PERM_ACCESS_SUBMISSIONS,
        $permissionService->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissionService->formScope($allowedForm)),
        $permissionService->scopedPermission(Permissions::PERM_SAVE_SUBMISSIONS, $permissionService->formScope($allowedForm)),
    ]);
    $status = new SubmissionStatus([
        'name' => 'Bulk Reviewed ' . uniqid(),
        'handle' => 'bulkReviewed' . uniqid(),
        'color' => 'blue',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(bulkActionAclIdentity($fixture['user']));

    try {
        $action = new SetSubmissionStatus(['statusId' => $status->id, 'statuses' => [$status]]);
        $result = $action->performAction(Submission::find()
            ->id([$fixture['allowedSubmission']->id, $fixture['restrictedSubmission']->id])
            ->status(null));
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }

    $allowed = Submission::find()->id($fixture['allowedSubmission']->id)->status(null)->one();
    $restricted = Submission::find()->id($fixture['restrictedSubmission']->id)->status(null)->one();

    expect($result)->toBeTrue()
        ->and((int)$allowed->statusId)->toBe((int)$status->id)
        ->and((int)$restricted->statusId)->not->toBe((int)$status->id);
})->group('security');

it('checks submission save access before bulk spam changes', function (): void {
    $permissionService = Formie::$plugin->getPermissions();
    $fixture = bulkActionAclFixture(fn($allowedForm) => [
        Permissions::PERM_ACCESS_SUBMISSIONS,
        $permissionService->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissionService->formScope($allowedForm)),
        $permissionService->scopedPermission(Permissions::PERM_SAVE_SUBMISSIONS, $permissionService->formScope($allowedForm)),
    ]);
    $originalUser = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(bulkActionAclIdentity($fixture['user']));

    try {
        $action = new SetSubmissionSpam(['spam' => 'markSpam']);
        $result = $action->performAction(Submission::find()
            ->id([$fixture['allowedSubmission']->id, $fixture['restrictedSubmission']->id])
            ->status(null));
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }

    $allowed = Submission::find()->id($fixture['allowedSubmission']->id)->status(null)->one();
    $restricted = Submission::find()->id($fixture['restrictedSubmission']->id)->status(null)->one();

    expect($result)->toBeTrue()
        ->and((bool)$allowed->isSpam)->toBeTrue()
        ->and((bool)$restricted->isSpam)->toBeFalse();
})->group('security');

it('checks form duplication access for every selected form', function (): void {
    $permissionService = Formie::$plugin->getPermissions();
    $fixture = bulkActionAclFixture(fn($allowedForm) => [
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_CREATE_FORMS,
        $permissionService->scopedPermission(Permissions::PERM_MANAGE_FORMS, $permissionService->formScope($allowedForm)),
    ]);
    $originalUser = Craft::$app->getUser()->getIdentity();
    $beforeCount = Form::find()->withoutCpIndexScope()->status(null)->count();
    Craft::$app->getUser()->setIdentity(bulkActionAclIdentity($fixture['user']));

    try {
        $action = new DuplicateForm();
        $result = $action->performAction(Form::find()
            ->withoutCpIndexScope()
            ->id([$fixture['allowedForm']->id, $fixture['restrictedForm']->id])
            ->status(null));
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }

    expect($result)->toBeTrue()
        ->and((int)Form::find()->withoutCpIndexScope()->status(null)->count())->toBe((int)$beforeCount + 1);
})->group('security');
