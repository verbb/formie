<?php
namespace verbb\formie\conditions;

final readonly class ConditionRule
{
    // Public Methods
    // =========================================================================

    public function __construct(public string $reference, public string $operator, public mixed $value = null, public array $metadata = [])
    {
    }
}
