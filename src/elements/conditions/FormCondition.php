<?php
namespace verbb\formie\elements\conditions;

use craft\elements\conditions\ElementCondition;

class FormCondition extends ElementCondition
{
    // Protected Methods
    // =========================================================================
    
    protected function conditionRuleTypes(): array
    {
        $rules = parent::conditionRuleTypes();

        $rules[] = PageCountConditionRule::class;

        return $rules;
    }
}
