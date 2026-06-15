<?php

declare(strict_types=1);

use craft\elements\User;

it('denies form actions for users without permissions', function (): void {
    $form = formie()
        ->form(['title' => 'Permission Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $user = new User();
    $user->admin = false;

    expect($form->canView($user))->toBeFalse()
        ->and($form->canSave($user))->toBeFalse()
        ->and($form->canDuplicate($user))->toBeFalse()
        ->and($form->canDelete($user))->toBeFalse();
});

it('allows admins to manage forms through element ACLs', function (): void {
    $form = formie()
        ->form(['title' => 'Admin Permission Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $user = new User();
    $user->admin = true;

    expect($form->canView($user))->toBeTrue()
        ->and($form->canSave($user))->toBeTrue()
        ->and($form->canDuplicate($user))->toBeTrue();
});
