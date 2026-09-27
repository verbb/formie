<?php
namespace verbb\formie\base;

trait FieldBrowserConditionTrait
{
    // Public Methods
    // =========================================================================

    public function getConditionValueType(): string
    {
        return \verbb\formie\conditions\ConditionCompiler::fieldType($this);
    }

    public function getConditionOperators(): array
    {
        return \verbb\formie\conditions\ConditionOperator::forType($this->getConditionValueType());
    }

    public function getBrowserConditions(): array
    {
        return $this->conditions()->toArray();
    }

    public function getConditionsJson(): ?string
    {
        return $this->conditions()->toJson();
    }
}
