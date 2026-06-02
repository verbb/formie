<?php

declare(strict_types=1);

dataset('field_behavior_visibility_methods', [
    ['singleLineTextField'],
    ['emailField'],
    ['numberField'],
    ['dateField'],
    ['phoneField'],
    ['multiLineTextField'],
]);

it('persists visible hidden and disabled states across core fields', function (string $method): void {
    foreach (['visible', 'hidden', 'disabled'] as $visibility) {
        $handle = strtolower($method) . $visibility;
        $form = formie()
            ->form(['title' => 'Visibility Matrix ' . $method . ' ' . $visibility])
            ->{$method}($handle, ['visibility' => $visibility])
            ->create();

        $field = $form->getFieldByHandle($handle);

        expect($field?->visibility)->toBe($visibility);

        if ($visibility === 'hidden') {
            expect($field?->getIsHidden())->toBeTrue();
        }

        if ($visibility === 'disabled') {
            expect($field?->getIsDisabled())->toBeTrue();
        }
    }
})->with('field_behavior_visibility_methods');
