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
        $set = $this->conditions();
        return $set->rules ? [
            ...(new \verbb\formie\conditions\ConditionCompiler())->compile($set, $this->getForm()),
            'isNested' => (bool)$this->getParentField(),
        ] : [];
    }

    public function getConditionsJson(): ?string
    {
        $conditions = $this->getBrowserConditions();
        return $conditions ? \craft\helpers\Json::encode($conditions) : null;
    }
}
