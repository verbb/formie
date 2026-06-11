<?php
namespace verbb\formie\fields\traits;

use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\SlotTag;

use craft\base\ElementInterface;
use craft\helpers\Localization;
use craft\validators\ArrayValidator;

use GraphQL\Type\Definition\Type;

trait OptionsLimitFieldTrait
{
    // Properties
    // =========================================================================

    public bool $limitOptions = false;
    public int|float|null $min = null;
    public int|float|null $max = null;


    // Public Methods
    // =========================================================================

    public function validateLimitOptions(ElementInterface $element): void
    {
        if (!$this->limitOptions) {
            return;
        }

        $arrayValidator = new ArrayValidator([
            'min' => $this->min ?: null,
            'max' => $this->max ?: null,
            'tooFew' => $this->min ? $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_OPTIONS, [
                'min' => $this->min,
            ]) : null,
            'tooMany' => $this->max ? $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_OPTIONS, [
                'max' => $this->max,
            ]) : null,
            'skipOnEmpty' => false,
        ]);

        $value = $element->getFieldValue($this->valueKey());

        if (!$arrayValidator->validate($value, $error)) {
            $element->addError($this->valueKey(), $error);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function normalizeOptionsLimitConstructorConfig(array &$config): void
    {
        foreach (['min', 'max'] as $name) {
            if (isset($config[$name]) && is_array($config[$name])) {
                $config[$name] = Localization::normalizeNumber($config[$name]['value'], $config[$name]['locale']);
            }
        }
    }

    protected function defineOptionsLimitGqlType(): array
    {
        return [
            'limitOptions' => [
                'name' => 'limitOptions',
                'type' => Type::boolean(),
            ],
            'min' => [
                'name' => 'min',
                'type' => Type::int(),
            ],
            'max' => [
                'name' => 'max',
                'type' => Type::int(),
            ],
        ];
    }

    protected function defineOptionsLimitValidationSchema(array $limitOptionsFieldConfig = []): array
    {
        return [
            SchemaHelper::limitOptionsField($limitOptionsFieldConfig),
            SchemaHelper::optionsLimitMinField(),
            SchemaHelper::minOptionsValidationMessage(),
            SchemaHelper::optionsLimitMaxField(),
            SchemaHelper::maxOptionsValidationMessage(),
        ];
    }

    protected function defineOptionsLimitRules(): array
    {
        return [
            [['min', 'max'], 'number'],
            [['max'], 'compare', 'compareAttribute' => 'min', 'operator' => '>='],
        ];
    }

    protected function getOptionsLimitElementValidationRules(): array
    {
        if (!$this->limitOptions) {
            return [];
        }

        return [
            [$this->handle, 'validateLimitOptions', 'skipOnEmpty' => false],
        ];
    }

    protected function applyOptionsLimitFieldAttributes(SlotTag $tag): SlotTag
    {
        if (!$this->limitOptions) {
            return $tag;
        }

        $tag->attributes['data-formie-min-options'] = $this->min ?: null;
        $tag->attributes['data-formie-max-options'] = $this->max ?: null;
        $tag->attributes = array_merge(
            $tag->attributes,
            ValidationMessagesHelper::optionsLimitClientAttributes($this, true, $this->min, $this->max),
        );

        return $tag;
    }

    protected function defineOptionsLimitValidationRules(): array
    {
        if (!$this->limitOptions) {
            return [];
        }

        return [[
            'type' => 'minmaxOptions',
            'min' => $this->min ?: null,
            'max' => $this->max ?: null,
        ]];
    }

    protected function getOptionsLimitClientInput(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
}
