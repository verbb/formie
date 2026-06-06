<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\fields\coercion\StringValueCoercer;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\StringFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\fields\conditions\TextFieldConditionRule;
use verbb\formie\fields\traits\AutocompleteFieldTrait;
use verbb\formie\elements\Submission;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidFieldException;

use GraphQL\Type\Definition\Type;

class SingleLineText extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Single-line Text');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/single-line-text/icon.svg';
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }


    // Traits
    // =========================================================================

    use AutocompleteFieldTrait;


    // Properties
    // =========================================================================

    public bool $limit = false;
    public ?int $min = null;
    public ?string $minType = 'characters';
    public ?int $max = null;
    public ?string $maxType = 'characters';
    public bool $uniqueValue = false;


    // Public Methods
    // =========================================================================

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $value !== '' ? $value : null;

        return parent::normalizeValue($value, $element);
    }

    public function getElementConditionRuleType(): ?string
    {
        return TextFieldConditionRule::class;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->limit) {
            if ($this->minType === 'characters') {
                $rules[] = [$this->handle, 'validateMinCharacters', 'skipOnEmpty' => false];
            }

            if ($this->maxType === 'characters') {
                $rules[] = [$this->handle, 'validateMaxCharacters'];
            }

            if ($this->minType === 'words') {
                $rules[] = [$this->handle, 'validateMinWords', 'skipOnEmpty' => false];
            }

            if ($this->maxType === 'words') {
                $rules[] = [$this->handle, 'validateMaxWords'];
            }
        }

        if ($this->uniqueValue) {
            $rules[] = [$this->handle, 'validateUniqueValue'];
        }

        return $rules;
    }

    public function validateMinCharacters(ElementInterface $element): void
    {
        $min = $this->min ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getCharacterCount($value);

        if ($count < $min) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_CHARACTERS, [
                'limit' => $min,
                'min' => $min,
            ]));
        }
    }

    public function validateMaxCharacters(ElementInterface $element): void
    {
        $max = $this->max ?? 0;

        if (!$max) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getCharacterCount($value);

        if ($count > $max) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_CHARACTERS, [
                'limit' => $max,
                'max' => $max,
            ]));
        }
    }

    public function validateMinWords(ElementInterface $element): void
    {
        $min = $this->min ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getWordCount($value);

        if ($count < $min) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_WORDS, [
                'limit' => $min,
                'min' => $min,
            ]));
        }
    }

    public function validateMaxWords(ElementInterface $element): void
    {
        $max = $this->max ?? 0;

        if (!$max) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());
        $count = StringHelper::getWordCount($value);

        if ($count > $max) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MAX_WORDS, [
                'limit' => $max,
                'max' => $max,
            ]));
        }
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput(),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), $this->defineAutocompleteGqlType(), [
            'limit' => [
                'name' => 'limit',
                'type' => Type::boolean(),
            ],
            'min' => [
                'name' => 'min',
                'type' => Type::int(),
            ],
            'minType' => [
                'name' => 'minType',
                'type' => Type::string(),
            ],
            'max' => [
                'name' => 'max',
                'type' => Type::int(),
            ],
            'maxType' => [
                'name' => 'maxType',
                'type' => Type::string(),
            ],
            'uniqueValue' => [
                'name' => 'uniqueValue',
                'type' => Type::boolean(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            $this->defineAutocompleteSettingSchema(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::limitValueField(),
            SchemaHelper::textLimitMinFields(),
            SchemaHelper::minCharactersValidationMessage(),
            SchemaHelper::minWordsValidationMessage(),
            SchemaHelper::textLimitMaxFields(),
            SchemaHelper::maxCharactersValidationMessage(),
            SchemaHelper::maxWordsValidationMessage(),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
            SchemaHelper::uniqueValueField(),
            SchemaHelper::uniqueValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
    

    // Protected Methods
    // =========================================================================

    protected function supportedDefaults(): array
    {
        return ['autocomplete'];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules = array_merge($rules, $this->defineAutocompleteRules());

        $rules[] = [['min', 'max'], 'number', 'integerOnly' => true];
        $rules[] = [['minType', 'maxType'], 'in', 'range' => ['characters', 'words']];

        return $rules;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            $value = $context->get('value');

            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'text',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'value' => $value ?? false,
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-single-line-text-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'text',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'autocomplete' => $this->getAutocompleteCoreAttribute(),
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::textLimitClientAttributes(
                    $this,
                    (bool)$this->limit,
                    $this->min,
                    $this->max,
                    $this->minType,
                    $this->maxType,
                )))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-single-line-text-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldLimit') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-field-limit' => true,
                    'data-formie-limit-text' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-field-note',
                        'formie-field-limit',
                        'formie-limit-text',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return StringValueCoercer::forCondition($value);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/single-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'textLimitConfig' => $this->getTextLimitClientConfig(ClientModule::RENDER_TARGET_CP_EDIT),
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return StringValueCoercer::asString($value);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        return StringValueCoercer::asArray($value);
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_TEXT],
            ]),
        ];
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return true;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'min' => $this->min,
            'max' => $this->max,
            'inputType' => 'text',
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if ($this->limit) {
            $modules[] = function(ClientModuleContext $context) {
                return new ClientModule([
                    'id' => 'text-limit',
                    'config' => $this->getTextLimitClientConfig($context->renderTarget),
                ]);
            };
        }

        return $modules;
    }

    protected function getTextLimitClientConfig(string $renderTarget): array
    {
        return [
            'allowOvertype' => $renderTarget === ClientModule::RENDER_TARGET_CP_EDIT,
        ];
    }

    protected function defineValueClass(): ?string
    {
        return StringFieldValue::class;
    }
}
