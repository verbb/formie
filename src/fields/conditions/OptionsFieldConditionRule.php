<?php
namespace verbb\formie\fields\conditions;

use verbb\formie\base\OptionsFieldInterface;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\OptionData;
use verbb\formie\fields\data\SingleOptionFieldData;

use craft\base\conditions\BaseMultiSelectConditionRule;

use yii\base\InvalidConfigException;

use Illuminate\Support\Collection;

class OptionsFieldConditionRule extends BaseMultiSelectConditionRule implements FieldConditionRuleInterface
{
    // Traits
    // =========================================================================

    use FieldConditionRuleTrait;


    // Protected Methods
    // =========================================================================

    protected function options(): array
    {
        $field = $this->field();

        return Collection::make($field->options)
            ->filter(fn(array $option) => (array_key_exists('value', $option) &&
                $option['value'] !== null &&
                $option['value'] !== '' &&
                $option['label'] !== null &&
                $option['label'] !== ''
            ))
            ->map(fn(array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
            ])
            ->all();
    }

    protected function inputHtml(): string
    {
        if (!$this->field() instanceof OptionsFieldInterface) {
            throw new InvalidConfigException();
        }

        return parent::inputHtml();
    }

    protected function elementQueryParam(): ?array
    {
        if (!$this->field() instanceof OptionsFieldInterface) {
            return null;
        }

        return $this->paramValue();
    }

    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof OptionsFieldInterface) {
            return true;
        }

        if ($value instanceof MultiOptionsFieldData) {
            $value = array_map(fn(OptionData $option) => $option->value, (array)$value);
        } else if ($value instanceof SingleOptionFieldData) {
            $value = $value->value;
        }

        return $this->matchValue($value);
    }
}
