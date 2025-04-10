<?php
namespace verbb\formie\fields\conditions;

use craft\elements\conditions\ElementConditionRuleInterface;

interface FieldConditionRuleInterface extends ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function setFieldUid(string $uid): void;
}
