<?php
namespace verbb\formie\fields\traits;

use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

use GraphQL\Type\Definition\Type;

trait OtherOptionFieldTrait
{
    // Constants
    // =========================================================================

    public const OTHER_OPTION_VALUE = '__other__';


    // Properties
    // =========================================================================

    public bool $enableOtherOption = false;
    public ?string $otherOptionLabel = null;


    // Public Methods
    // =========================================================================

    public function getOtherOptionLabel(): string
    {
        $label = trim((string)($this->otherOptionLabel ?? ''));

        if ($label !== '') {
            return $label;
        }

        return Craft::t('formie', 'Other');
    }

    public function getOtherOptionValue(): string
    {
        return self::OTHER_OPTION_VALUE;
    }

    public function getOtherOptionHtmlName(): string
    {
        if ($this->multi) {
            return $this->getHtmlName('[other]');
        }

        return $this->getHtmlName('Other');
    }

    public function isOtherOptionStoredValue(mixed $value): bool
    {
        if (!$this->enableOtherOption) {
            return false;
        }

        $stringValue = trim((string)$value);

        if ($stringValue === '' || $stringValue === self::OTHER_OPTION_VALUE) {
            return false;
        }

        return !in_array($stringValue, $this->getValidationOptionValues(), true);
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->enableOtherOption) {
            $otherText = $this->extractOtherOptionTextFromRequest($value);
            $value = $this->stripOtherOptionRequestMetadata($value);
            $value = $this->applyOtherOptionText($value, $otherText);
        }

        return parent::normalizeValueFromRequest($value, $element);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $normalized = parent::normalizeValue($value, $element);

        return $this->markOtherOptionValuesValid($normalized);
    }

    public function getFieldOptions(): array
    {
        $options = parent::getFieldOptions();

        if (!$this->enableOtherOption || $this->getOptionsMode() !== 'static') {
            return $options;
        }

        $options[] = [
            'label' => $this->getOtherOptionLabel(),
            'value' => self::OTHER_OPTION_VALUE,
            'disabled' => false,
            'isOther' => true,
        ];

        return $options;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->enableOtherOption) {
            $rules[] = [$this->handle, 'validateOtherOptionText', 'skipOnEmpty' => false];
        }

        if (!$this->enableOtherOption || !$this->usesStrictOptionValidation()) {
            return $rules;
        }

        $rules = array_values(array_filter($rules, static function($rule): bool {
            return !(is_array($rule) && ($rule[1] ?? null) === 'in');
        }));

        $rules[] = [$this->handle, 'validateOtherOptionValues'];

        return $rules;
    }

    public function validateOtherOptionText(ElementInterface $element): void
    {
        if (!$this->enableOtherOption) {
            return;
        }

        $fields = Craft::$app->getRequest()->getBodyParam('fields', []);

        if (!is_array($fields)) {
            return;
        }

        $hasOtherSelected = false;
        $otherText = '';

        if ($this->multi) {
            $raw = $fields[$this->handle] ?? null;

            if (is_array($raw)) {
                $hasOtherSelected = in_array(self::OTHER_OPTION_VALUE, $raw, true);
                $otherText = trim((string)($raw['other'] ?? ''));
            }
        } else {
            $raw = $fields[$this->handle] ?? null;
            $hasOtherSelected = (string)$raw === self::OTHER_OPTION_VALUE;
            $otherText = trim((string)($fields[$this->handle . 'Other'] ?? ''));
        }

        if ($hasOtherSelected && $otherText === '') {
            $element->addError($this->valueKey(), Craft::t('formie', 'Please enter a value for “{label}”.', [
                'label' => $this->getOtherOptionLabel(),
            ]));
        }
    }

    public function validateOtherOptionValues(mixed $value): void
    {
        if (!$this->enableOtherOption) {
            return;
        }

        $allowed = $this->getValidationOptionValues();
        $selectedValues = $this->extractSelectedValuesForValidation($value);

        foreach ($selectedValues as $selectedValue) {
            $selectedValue = trim((string)$selectedValue);

            if ($selectedValue === '' || $selectedValue === self::OTHER_OPTION_VALUE) {
                continue;
            }

            if (in_array($selectedValue, $allowed, true)) {
                continue;
            }

            // Custom “other” text is allowed when the feature is enabled.
            continue;
        }
    }

    public function validateOptions(): void
    {
        parent::validateOptions();

        if (!$this->enableOtherOption || $this->getOptionsMode() !== 'static') {
            return;
        }

        foreach ($this->options() as $option) {
            if (isset($option['optgroup'])) {
                continue;
            }

            if ((string)($option['value'] ?? '') === self::OTHER_OPTION_VALUE) {
                $this->addError('options', Craft::t('formie', 'Option values cannot use the reserved “other” value.'));
                break;
            }
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineOtherOptionSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Enable “Other” Option'),
                'instructions' => Craft::t('formie', 'Allow users to choose an “Other” option and enter a custom value when the predefined options don’t fit.'),
                'name' => 'enableOtherOption',
                'if' => 'optionsMode == "static"',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', '“Other” Option Label'),
                'instructions' => Craft::t('formie', 'The label shown for the “Other” option.'),
                'name' => 'otherOptionLabel',
                'if' => 'enableOtherOption && optionsMode == "static"',
                'placeholder' => Craft::t('formie', 'Other'),
            ]),
        ];
    }

    protected function defineOtherOptionGqlTypes(): array
    {
        return [
            'enableOtherOption' => [
                'name' => 'enableOtherOption',
                'type' => Type::boolean(),
            ],
            'otherOptionLabel' => [
                'name' => 'otherOptionLabel',
                'type' => Type::string(),
            ],
        ];
    }

    protected function otherOptionSettingsAttributes(): array
    {
        return [
            'enableOtherOption',
            'otherOptionLabel',
        ];
    }

    protected function defineOtherOptionValidationRules(): array
    {
        if (!$this->enableOtherOption) {
            return [];
        }

        return [[
            'type' => 'otherOptionText',
        ]];
    }

    protected function defineOtherOptionFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if (!$this->enableOtherOption) {
            return null;
        }

        $form = $context->form;
        $otherOptionValue = self::OTHER_OPTION_VALUE;

        return match ($key) {
            'fieldOtherOption' => SlotTag::make('div')
                ->core([
                    'data-formie-field-option' => true,
                    'data-formie-radio-option' => true,
                    'data-formie-other-option-row' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-option',
                        'formie-radio-option',
                        'formie-other-option',
                    ],
                ]),
            'fieldOtherOptionInput' => SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'radio',
                    'id' => $this->getHtmlId($form, $otherOptionValue),
                    'name' => $this->getHtmlName(($this->hasMultiNamespace ? '[]' : null)),
                    'value' => $otherOptionValue,
                    'data-formie-input' => true,
                    'data-formie-radio-input' => true,
                    'data-formie-other-option' => true,
                    'data-formie-input-id' => $this->getHtmlDataId($form, $otherOptionValue),
                    'data-formie-input-type' => 'radio',
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-radio-input',
                        'formie-other-option-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes()),
            'fieldOtherOptionLabel' => SlotTag::make('label')
                ->core([
                    'data-formie-field-option-label' => true,
                    'data-formie-radio-option-label' => true,
                    'data-formie-other-option-label' => true,
                    'for' => $this->getHtmlId($form, $otherOptionValue),
                ])
                ->theme([
                    'class' => [
                        'formie-field-option-label',
                        'formie-radio-option-label',
                        'formie-other-option-label',
                    ],
                ]),
            'fieldOtherOptionText' => SlotTag::make('input')
                ->core([
                    'type' => 'text',
                    'id' => $this->getHtmlId($form, 'other'),
                    'name' => $this->getOtherOptionHtmlName(),
                    'data-formie-other-option-text' => true,
                    'data-formie-input' => true,
                    'data-formie-input-type' => 'text',
                    'aria-label' => Craft::t('formie', '{label}, please specify', [
                        'label' => $this->getOtherOptionLabel(),
                    ]),
                    'autocomplete' => 'off',
                ])
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-other-option-text',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes()),
            default => null,
        };
    }

    protected function extractOtherOptionTextFromRequest(mixed $value): ?string
    {
        if (is_array($value) && array_key_exists('other', $value)) {
            return (string)$value['other'];
        }

        if (!$this->multi) {
            $fields = Craft::$app->getRequest()->getBodyParam('fields', []);

            if (is_array($fields) && array_key_exists($this->handle . 'Other', $fields)) {
                return (string)$fields[$this->handle . 'Other'];
            }
        }

        return null;
    }

    protected function stripOtherOptionRequestMetadata(mixed $value): mixed
    {
        if (!is_array($value) || !array_key_exists('other', $value)) {
            return $value;
        }

        if (!$this->multi && array_key_exists('value', $value)) {
            return $value['value'];
        }

        unset($value['other']);

        return $this->multi ? array_values($value) : $value;
    }

    protected function applyOtherOptionText(mixed $value, ?string $otherText): mixed
    {
        $otherText = trim((string)($otherText ?? ''));

        if ($this->multi && is_array($value)) {
            return array_map(static function($item) use ($otherText) {
                return (string)$item === self::OTHER_OPTION_VALUE ? $otherText : $item;
            }, $value);
        }

        if ((string)$value === self::OTHER_OPTION_VALUE) {
            return $otherText;
        }

        return $value;
    }

    protected function markOtherOptionValuesValid(mixed $value): mixed
    {
        if (!$this->enableOtherOption || !($value instanceof MultiOptionFieldValue || $value instanceof SingleOptionFieldValue)) {
            return $value;
        }

        $allowed = $this->getValidationOptionValues();

        foreach ($value->all() as $option) {
            $optionValue = (string)($option->value ?? '');

            if ($option->valid || $optionValue === '' || in_array($optionValue, $allowed, true)) {
                continue;
            }

            $option->valid = true;

            if (trim((string)($option->label ?? '')) === '') {
                $option->label = $optionValue;
            }
        }

        return $value;
    }

    protected function extractSelectedValuesForValidation(mixed $value): array
    {
        if ($value instanceof MultiOptionFieldValue) {
            return $value->values();
        }

        if ($value instanceof SingleOptionFieldValue) {
            return [(string)($value->value ?? '')];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_scalar($value) || $value === null) {
            return [(string)$value];
        }

        return [];
    }
}
