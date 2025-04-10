<?php
namespace verbb\formie\fields\conditions;

use craft\base\conditions\BaseTextConditionRule;

class TextFieldConditionRule extends BaseTextConditionRule implements FieldConditionRuleInterface
{
    // Traits
    // =========================================================================

    use FieldConditionRuleTrait;


    // Protected Methods
    // =========================================================================

    protected function elementQueryParam(): ?string
    {
        return $this->paramValue();
    }

    protected function matchFieldValue($value): bool
    {
        return $this->matchValue($value);
    }
}
