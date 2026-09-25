<?php
namespace verbb\formie\workflow\tasks;

use verbb\formie\models\SubmissionOutcome;

final class TaskResult
{
    // Static Methods
    // =========================================================================

    public static function continue(): self
    {
        return new self(null);
    }

    public static function stop(SubmissionOutcome $outcome): self
    {
        return new self($outcome);
    }


    // Private Methods
    // =========================================================================

    private function __construct(public readonly ?SubmissionOutcome $outcome)
    {
    }
}
