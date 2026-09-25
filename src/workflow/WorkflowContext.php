<?php
namespace verbb\formie\workflow;

use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionOutcome;

class WorkflowContext
{
    // Static Methods
    // =========================================================================

    public static function current(): ?self
    {
        return self::$_stack ? self::$_stack[array_key_last(self::$_stack)] : null;
    }

    public static function push(self $context): void
    {
        self::$_stack[] = $context;
    }

    public static function pop(): void
    {
        array_pop(self::$_stack);
    }


    // Properties
    // =========================================================================

    public ?FieldLayoutPage $nextPage = null;
    public bool $attemptedCompletion = false;
    public bool $becameComplete = false;
    public bool $processingSuccess = false;
    public array $taskState = [];
    public ?PaymentDecision $paymentDecision = null;
    public ?SubmissionOutcome $outcome = null;
    public array $stages = [];

    private static array $_stack = [];


    // Public Methods
    // =========================================================================

    public function __construct(public readonly SubmissionCommand $command)
    {
    }

    public function isPageAdvance(): bool
    {
        return $this->command->usesVisitorProgression()
            && $this->command->operation === SubmissionOperation::SUBMIT
            && $this->command->navigation === NavigationIntent::ADVANCE
            && $this->nextPage !== null;
    }

    public function isCompletion(): bool
    {
        return $this->becameComplete && !$this->command->submission->isIncomplete;
    }

    public function result(SubmissionOutcomeType $type, array $data = []): SubmissionOutcome
    {
        $submission = $this->command->submission;

        return new SubmissionOutcome(
            $type,
            $submission->id ? (int)$submission->id : null,
            $submission->uid ?: null,
            $submission->id ? $submission->stateVersion : null,
            $this->nextPage?->id,
            $submission->getErrors(),
            array_merge(['payment' => $this->paymentDecision?->toArray()], $data),
        );
    }
}
