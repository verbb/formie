<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\coercion\StringValueCoercer;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\fields\conditions\TextFieldConditionRule;
use verbb\formie\fields\traits\AutocompleteFieldTrait;
use verbb\formie\fields\traits\TextLimitFieldTrait;
use verbb\formie\fields\traits\UniqueValueFieldTrait;
use verbb\formie\fields\values\StringFieldValue;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Template;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;

class MultiLineText extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Multi-line Text');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/multi-line-text/icon.svg';
    }

    public static function dbType(): string
    {
        return Schema::TYPE_TEXT;
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';


    // Traits
    // =========================================================================

    use AutocompleteFieldTrait;
    use TextLimitFieldTrait;
    use UniqueValueFieldTrait;


    // Properties
    // =========================================================================

    public bool $useRichText = false;
    public ?array $richTextButtons = ['bold', 'italic'];
    public bool $plainTextPaste = false;


    // Public Methods
    // =========================================================================

    public function fieldKind(): string
    {
        return self::KIND_TEXTAREA;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->useRichText && is_string($value) && $value !== '') {
            $value = StringHelper::cleanString($value);
        } else if ($value === '') {
            $value = null;
        }

        $value = parent::normalizeValue($value, $element);

        if (!$this->useRichText && ($value === '' || $value === null)) {
            return null;
        }

        return $value;
    }

    protected function shouldTrimNormalizedPlainText(): bool
    {
        return !$this->useRichText;
    }

    public function getElementConditionRuleType(): ?string
    {
        return TextFieldConditionRule::class;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        foreach ($this->getTextLimitElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->getUniqueValueElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewTextarea(),
        ];
    }

    public function getRichTextButtons(): ?array
    {
        $order = array_map(function($item) {
            return $item['value'];
        }, $this->getButtonOptions());

        // Return the order of buttons as they were defined in our field
        if ($this->richTextButtons) {
            usort($this->richTextButtons, function ($a, $b) use ($order) {
                $pos_a = array_search($a, $order);
                $pos_b = array_search($b, $order);

                return $pos_a - $pos_b;
            });
        }

        return $this->richTextButtons;
    }

    public function getButtonOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Bold'), 'value' => 'bold'],
            ['label' => Craft::t('formie', 'Italic'), 'value' => 'italic'],
            ['label' => Craft::t('formie', 'Underline'), 'value' => 'underline'],
            ['label' => Craft::t('formie', 'Strike-through'), 'value' => 'strikethrough'],
            ['label' => Craft::t('formie', 'Heading 1'), 'value' => 'heading1'],
            ['label' => Craft::t('formie', 'Heading 2'), 'value' => 'heading2'],
            ['label' => Craft::t('formie', 'Paragraph'), 'value' => 'paragraph'],
            ['label' => Craft::t('formie', 'Quote'), 'value' => 'quote'],
            ['label' => Craft::t('formie', 'Ordered List'), 'value' => 'olist'],
            ['label' => Craft::t('formie', 'Unordered List'), 'value' => 'ulist'],
            ['label' => Craft::t('formie', 'Code'), 'value' => 'code'],
            ['label' => Craft::t('formie', 'Horizontal Rule'), 'value' => 'line'],
            ['label' => Craft::t('formie', 'Link'), 'value' => 'link'],
            ['label' => Craft::t('formie', 'Image'), 'value' => 'image'],
            ['label' => Craft::t('formie', 'Align Left'), 'value' => 'alignleft'],
            ['label' => Craft::t('formie', 'Align Center'), 'value' => 'aligncenter'],
            ['label' => Craft::t('formie', 'Align Right'), 'value' => 'alignright'],
            ['label' => Craft::t('formie', 'Clear Formatting'), 'value' => 'clear'],
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(
            parent::getSettingGqlTypes(),
            $this->defineAutocompleteGqlType(),
            $this->defineTextLimitGqlType(),
            [
                'useRichText' => [
                    'name' => 'useRichText',
                    'type' => Type::boolean(),
                ],
                'richTextButtons' => [
                    'name' => 'richTextButtons',
                    'type' => Type::listOf(Type::string()),
                ],
                'plainTextPaste' => [
                    'name' => 'plainTextPaste',
                    'type' => Type::boolean(),
                ],
            ],
            $this->defineUniqueValueGqlType(),
        );
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
            SchemaHelper::textareaField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'rows' => '3',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            $this->defineAutocompleteSettingSchema([
                'if' => '!useRichText',
            ]),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            ...$this->defineTextLimitValidationSchema(),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
            ...$this->defineUniqueValueValidationSchema(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Rich Text Field'),
                'instructions' => Craft::t('formie', 'Whether to display this field with a rich text editor for users to enter values with.'),
                'name' => 'useRichText',
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Rich Text Buttons'),
                'instructions' => Craft::t('formie', 'Select which formatting buttons available for users to use.'),
                'name' => 'richTextButtons',
                'showAllOption' => false,
                'if' => 'useRichText',
                'options' => $this->getButtonOptions(),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Paste as Plain Text'),
                'instructions' => Craft::t('formie', 'When enabled, pasted content is inserted without formatting (for example from Word or websites).'),
                'name' => 'plainTextPaste',
                'if' => 'useRichText',
            ]),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
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

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return SlotTag::make('textarea')
                ->core($this->applyTextLimitInputAttributes([
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'required' => $this->required ? true : null,
                    'data-formie-textarea' => true,
                    'data-formie-multi-line-text-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'textarea',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'autocomplete' => $this->getAutocompleteCoreAttribute(),
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ]))
                ->theme([
                    'class' => [
                        'formie-textarea',
                        'formie-multi-line-text-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($textLimitTag = $this->defineTextLimitFieldSlotTag($key, $context)) {
            return $textLimitTag;
        }

        if ($key === 'fieldRichText') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-rich-text' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-rich-text',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        foreach ($this->defineAutocompleteRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->defineTextLimitRules() as $rule) {
            $rules[] = $rule;
        }

        foreach ($this->defineUniqueValueRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $form = null;

        if ($element instanceof Submission) {
            $form = $element->getForm();
        }

        return Craft::$app->getView()->renderTemplate('formie/_formfields/multi-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'form' => $form,
            'textLimitConfig' => $this->getTextLimitClientConfig(ClientModule::RENDER_TARGET_CP_EDIT),
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $stringValue = StringValueCoercer::asString($value);

        if ($this->useRichText) {
            return StringHelper::cleanString($stringValue);
        }

        return $stringValue;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        $stringValue = $this->defineValueAsString($value, $element);

        if ($this->useRichText) {
            return Template::raw($stringValue);
        }

        return $stringValue;
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->realText;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_TEXT],
                'content' => Variables::CONTENT_ANY,
            ]),
        ];
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), $this->getTextLimitClientInput());
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        foreach ($this->defineTextLimitClientModules() as $module) {
            $modules[] = $module;
        }

        if ($this->useRichText) {
            $modules[] = new ClientModule([
                'id' => 'rich-text',
                'config' => [
                    'buttons' => $this->getRichTextButtons(),
                ],
            ]);
        }

        return $modules;
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return !$this->useRichText;
    }

    protected function defineValueClass(): ?string
    {
        return StringFieldValue::class;
    }
}
