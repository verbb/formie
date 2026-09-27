<?php
namespace verbb\formie\conditions;

/** A missing or malformed operand is not a negative predicate. */
final readonly class ConditionEvaluation
{
    // Static Methods
    // =========================================================================

    public static function invalid(string $code, ?int $rule = null): self
    {
        return new self(null, [['code' => $code, 'rule' => $rule]]);
    }


    // Public Methods
    // =========================================================================

    public function __construct(public ?bool $value, public array $diagnostics = [])
    {
    }

    public function matches(): bool
    {
        return $this->value === true;
    }

    public function permits(bool $whenMatched = true): bool
    {
        return $this->value !== null && $this->value === $whenMatched;
    }

    public function hides(mixed $effect): bool
    {
        return in_array($effect, ['hide', 'disable'], true) ? $this->matches() : !$this->matches();
    }
}
