<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/Factories/functions.php';

pest()
    ->extend(Tests\General\TestCase::class)
    ->in('Fields', 'Forms', 'Submissions', 'Queries', 'Integrations', 'Performance', 'Migrations', 'Frontend', 'Security', 'Theme', 'Services', 'Helpers', 'Notifications', 'Reports', 'Prosemirror', 'Payments', 'Cleanup', 'Models');

pest()->in('Unit');

expect()->extend('toHaveFieldError', function (string $fieldHandle) {
    $errors = $this->value->getErrors($fieldHandle);

    if (!$errors) {
        $errors = $this->value->getErrors("field:{$fieldHandle}");
    }

    return expect($errors)->not->toBeEmpty();
});

// Synthetic performance fixtures must not accumulate into later workload sizes.
// Keep transactions local to these tests; delivery/concurrency tests need commits.
pest()->beforeEach(function (): void {
    $this->performanceTransaction = Craft::$app->getDb()->beginTransaction();
})->afterEach(function (): void {
    $this->performanceTransaction->rollBack();
    \verbb\formie\Formie::$plugin->getForms()->invalidateFormCaches();
    \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();
    Craft::$app->getGql()->flushCaches();
})->in('Performance');
