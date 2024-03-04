<?php
namespace verbb\formie\elements\conditions;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class PageCountConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('formie', 'Page Count');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['pageCount'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->pageCount($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->pageCount);
    }
}
