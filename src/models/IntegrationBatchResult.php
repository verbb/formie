<?php
namespace verbb\formie\models;

use verbb\formie\enums\IntegrationStatus;

/** Keeps each result, including repeated attempts, instead of collapsing it to a boolean. */
final class IntegrationBatchResult
{
    // Properties
    // =========================================================================

    private array $_results = [];
    private bool $_stoppedOnFailure = false;


    // Public Methods
    // =========================================================================

    public function record(string $step, IntegrationResult $result): void
    {
        $this->_results[] = ['step' => $step, 'result' => $result];
    }

    public function results(): array
    {
        return $this->_results;
    }

    public function accepts(array $statuses = ['succeeded', 'skipped']): bool
    {
        foreach ($this->_results as $item) {
            if (!in_array($item['result']->status->value, $statuses, true)) {
                return false;
            }
        }
        return true;
    }

    public function markStoppedOnFailure(): void
    {
        $this->_stoppedOnFailure = true;
    }

    public function stoppedOnFailure(): bool
    {
        return $this->_stoppedOnFailure;
    }

    public function failedHandles(): array
    {
        return array_column(array_filter($this->_results, fn($item) => !in_array($item['result']->status, [IntegrationStatus::Succeeded, IntegrationStatus::Skipped], true)), 'step');
    }
}
