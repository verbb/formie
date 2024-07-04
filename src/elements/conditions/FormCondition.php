<?php
namespace verbb\formie\elements\conditions;

use craft\elements\conditions\ElementCondition;

class FormCondition extends ElementCondition
{
    // Protected Methods
    // =========================================================================
    
    protected function selectableConditionRules(): array
    {
        $rules = parent::selectableConditionRules();

        $rules[] = PageCountConditionRule::class;

        return $rules;
    }
}
