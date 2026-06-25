<?php
namespace verbb\formie\elements\conditions;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\conditions\HintableConditionRuleTrait;
use craft\elements\db\ElementQueryInterface;

class FormStatusConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    // Traits
    // =========================================================================

    use HintableConditionRuleTrait;


    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('formie', 'Form Status');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['formStatusId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->formStatusId($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Form $element */
        return $this->matchValue((string)$element->getFormStatusId());
    }


    // Protected Methods
    // =========================================================================

    protected function options(): array
    {
        $options = [];

        foreach (Formie::$plugin->getFormStatuses()->getAllStatuses() as $status) {
            $label = $status->name;

            if ($this->showLabelHint()) {
                $label .= " ({$status->handle})";
            }

            $options[(string)$status->id] = $label;
        }

        return $options;
    }
}
