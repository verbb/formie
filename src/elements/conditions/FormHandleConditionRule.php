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

class FormHandleConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    // Traits
    // =========================================================================

    use HintableConditionRuleTrait;


    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('formie', 'Form');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['handle'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->handle($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Form $element */
        return $this->matchValue($element->handle);
    }


    // Protected Methods
    // =========================================================================

    protected function options(): array
    {
        $forms = Formie::$plugin->getForms()->getAllForms();
        usort($forms, fn(Form $a, Form $b) => strcasecmp($a->title ?? '', $b->title ?? ''));

        $options = [];
        foreach ($forms as $form) {
            if ($form->handle === null || $form->handle === '') {
                continue;
            }

            $label = ($form->title ?? '') !== ''
                ? $form->title
                : $form->handle;

            if ($this->showLabelHint()) {
                $label .= " ({$form->handle})";
            }

            $options[$form->handle] = $label;
        }

        return $options;
    }
}
