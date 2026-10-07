<?php
namespace verbb\formie\base;

use verbb\formie\conditions\ConditionCompiler;
use verbb\formie\conditions\ConditionOperator;

use craft\helpers\Json;

trait FieldBrowserConditionTrait
{
    // Public Methods
    // =========================================================================

    public function getConditionValueType(): string
    {
        return ConditionCompiler::fieldType($this);
    }

    public function getConditionOperators(): array
    {
        return ConditionOperator::forType($this->getConditionValueType());
    }

    public function getBrowserConditions(): array
    {
        $set = $this->conditions();
        return $set->rules ? [
            ...(new ConditionCompiler())->compile($set, $this->getForm()),
            'isNested' => (bool)$this->getParentField(),
        ] : [];
    }

    public function getConditionsJson(): ?string
    {
        $conditions = $this->getBrowserConditions();
        return $conditions ? Json::encode($conditions) : null;
    }
}
