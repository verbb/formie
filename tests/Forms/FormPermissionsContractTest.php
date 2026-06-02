<?php

declare(strict_types=1);

use craft\elements\User;

it('keeps explicit form permission API contracts for delete and duplicate', function (): void {
    $form = formie()
        ->form(['title' => 'Permission Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $user = new User();

    expect($form->canDuplicate($user))->toBeTrue()
        ->and($form->canDelete($user))->toBeBool();
});
