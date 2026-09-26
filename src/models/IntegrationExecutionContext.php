<?php
namespace verbb\formie\models;

/** Immutable authority and identity passed to semantic delivery events. */
final class IntegrationExecutionContext
{
    // Properties
    // =========================================================================

    public readonly int $submissionId;
    public readonly int $formId;
    public readonly string $binding;
    public readonly string $executionUid;
    public readonly string $execution;
    public readonly string $reason;
    public readonly bool $eligible;
    public readonly array $overrides;


    // Public Methods
    // =========================================================================

    public function __construct(int $submissionId, int $formId, string $binding, string $executionUid, string $execution = 'queued', string $reason = 'automatic', bool $eligible = true, array $overrides = [])
    {
        $this->submissionId = $submissionId;
        $this->formId = $formId;
        $this->binding = $binding;
        $this->executionUid = $executionUid;
        $this->execution = $execution;
        $this->reason = $reason;
        $this->eligible = $eligible;
        $this->overrides = $overrides;
    }
}
