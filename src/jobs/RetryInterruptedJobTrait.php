<?php
namespace verbb\formie\jobs;

use Craft;

trait RetryInterruptedJobTrait
{
    // Public Methods
    // =========================================================================

    public function getTtr(): int
    {
        return Craft::$app->getQueue()->ttr;
    }

    public function canRetry(mixed $attempt, mixed $error): bool
    {
        // Yii checks an expired reservation with no error before executing it.
        // Actual delivery errors still need an operator to retry the failed job.
        return $error === null;
    }
}
