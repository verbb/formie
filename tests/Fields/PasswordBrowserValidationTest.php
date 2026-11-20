<?php

declare(strict_types=1);

use verbb\formie\fields\Password;

// Forms that validate in the browser get the password requirements and their messages, as the server checks them.
it('sends the password requirements as a browser validation rule', function(): void {
    $field = new Password(['handle' => 'secret', 'label' => 'Secret', 'passwordMinLength' => 8, 'passwordRequireUppercase' => true]);
    $rule = current(array_filter($field->browserValidationRules(), static fn(array $rule): bool => $rule['type'] === 'password'));

    expect($rule)->toMatchArray(['type' => 'password', 'min' => 8, 'uppercase' => true, 'lowercase' => false, 'specialCharacter' => false])
        ->and($rule['messages']['minCharacters'])->toBe('Secret must be no less than 8 characters.')
        ->and($rule['messages']['passwordUppercase'])->toBe('Secret must contain at least one uppercase letter.');
    expect(array_column((new Password(['handle' => 'open']))->browserValidationRules(), 'type'))->not->toContain('password');
});
