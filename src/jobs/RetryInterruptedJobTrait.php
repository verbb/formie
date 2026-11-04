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
        // Delivery errors retain the queue's configured attempt limit.
        return $error === null || $attempt < Craft::$app->getQueue()->attempts;
    }
}
