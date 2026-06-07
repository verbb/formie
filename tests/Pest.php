<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/Factories/functions.php';

pest()
    ->extend(Tests\General\TestCase::class)
    ->in('Fields', 'Forms', 'Submissions', 'Queries', 'Integrations', 'Performance', 'Migrations', 'Frontend', 'Security', 'Theme', 'Services', 'Helpers', 'Notifications');

expect()->extend('toHaveFieldError', function (string $fieldHandle) {
    $errors = $this->value->getErrors($fieldHandle);

    if (!$errors) {
        $errors = $this->value->getErrors("field:{$fieldHandle}");
    }

    return expect($errors)->not->toBeEmpty();
});
