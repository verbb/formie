<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;

it('builds stable group-scoped permission keys', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->groupScope('marketing'))->toBe('group:marketing')
        ->and($permissions->scopedPermission(Permissions::PERM_MANAGE_FORMS, 'group:marketing'))
        ->toBe('formie-manageForms:group:marketing');
});

it('allows creating a submission without granting permission to edit saved submissions', function (string $scope): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $permissions = Formie::$plugin->getPermissions();
    $allowed = match ($scope) {
        'form' => 'formie-createSubmissions:' . $form->uid,
        'group' => 'formie-createSubmissions:' . $permissions->groupScope($permissions->getFormGroupHandle($form)),
        default => 'formie-createSubmissions',
    };
    $user = new class($allowed) extends User {
        public function __construct(private string $allowedPermission) { parent::__construct(); }
        public function can(string $permission): bool { return $permission === $this->allowedPermission; }
    };
    $submission = new Submission();
    $submission->setForm($form);
    expect($submission->canSave($user))->toBeTrue();

    $saved = formie()->submission($form)->with(['message' => 'Existing submission'])->save();
    expect($saved->canSave($user))->toBeFalse();
})->with(['global', 'form', 'group']);

it('resolves ungrouped forms to the ungrouped permission bucket', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    $form = formie()
        ->form(['title' => 'Ungrouped Permission Form'])
        ->singleLineTextField('fullName')
        ->create();

    expect($permissions->getFormGroupHandle($form))->toBe(Permissions::GROUP_UNGROUPED);
});

it('defaults dedicated per-form permissions to off for new forms', function (): void {
    $form = formie()
        ->form(['title' => 'Default Permission Mode'])
        ->singleLineTextField('fullName')
        ->create();

    expect($form->getSettings()->usePerFormPermissions)->toBeFalse();
});

it('allows admins and denies guests for form element ACLs', function (): void {
    $form = formie()
        ->form(['title' => 'ACL Form'])
        ->singleLineTextField('fullName')
        ->create();

    $admin = new User();
    $admin->admin = true;

    $guest = new class extends User {
        public function can(string $permission): bool { return false; }
    };
    $guest->admin = false;

    expect($form->canView($admin))->toBeTrue()
        ->and($form->canSave($admin))->toBeTrue()
        ->and($form->canDuplicate($admin))->toBeTrue()
        ->and($form->canView($guest))->toBeFalse()
        ->and($form->canSave($guest))->toBeFalse()
        ->and($form->canDuplicate($guest))->toBeFalse();
});

it('can save a form group and assign the form to it', function (): void {
    $group = new FormGroup([
        'name' => 'Marketing',
        'handle' => 'marketing' . uniqid(),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $form = formie()
        ->form(['title' => 'Grouped Form', 'groupId' => $group->id])
        ->singleLineTextField('fullName')
        ->create();

    expect(Formie::$plugin->getPermissions()->getFormGroupHandle($form))->toBe($group->handle);
});

it('exposes import and export permission helpers', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->canImportForms(null))->toBeFalse()
        ->and($permissions->canExportForms(null))->toBeFalse();
});
