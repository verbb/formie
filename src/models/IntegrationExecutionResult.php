<?php
namespace verbb\formie\models;

use craft\base\Model;

/**
 * Outcome of running one integration step batch (immediate or queued handles).
 * Callers use this to fail queue jobs and stop later phases.
 */
class IntegrationExecutionResult extends Model
{
    // Properties
    // =========================================================================

    public bool $success = true;
    public bool $stoppedOnFailure = false;
    public int $attempted = 0;
    public int $succeeded = 0;
    public array $failedHandles = [];


    // Public Methods
    // =========================================================================

    public function recordAttempt(string $handle, bool $succeeded): void
    {
        $this->attempted++;

        if ($succeeded) {
            $this->succeeded++;
            return;
        }

        $this->success = false;
        $this->failedHandles[] = $handle;
    }

    public function markStoppedOnFailure(): void
    {
        $this->stoppedOnFailure = true;
        $this->success = false;
    }
}
