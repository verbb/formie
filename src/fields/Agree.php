<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\helpers\Template;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;

use Twig\Markup;

class Agree extends Field implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Agree');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/agree/icon.svg';
    }

    public static function dbType(): string
    {
        return Schema::TYPE_BOOLEAN;
    }


    // Properties
    // =========================================================================

    public ?array $description = null;
    public ?string $checkedValue = null;
    public ?string $uncheckedValue = null;

    // Private due to parsing done at render-time, not before
    private ?string $_descriptionHtml = null;


    // Public Methods
    // =========================================================================

    public function attributes(): array
    {
        $names = parent::attributes();
        
        // Define `descriptionHtml` as an extra attribute, rather than a property.
        // It's not a public property to ensure it's not saved to the field settings. 
        // Without this, `setDescriptionHtml()` would not be called, and this settings could not be manipulated
        // from the front-end `setFieldSettings()`. We also cannot set this value in `init()` due to when containing a Link mark
        // `parseRefTags()` causes an infinite loop.
        $names[] = 'descriptionHtml';

        return $names;
    }

    public function hasEmailPlaceholder(): bool
    {
        return false;
    }

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['defaultValue'] = false;
        $settings['labelPosition'] = HiddenPosition::class;
        $settings['checkedValue'] = Craft::t('app', 'Yes');
        $settings['uncheckedValue'] = Craft::t('app', 'No');

        return $settings;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        // Use the default "empty" checks, but `false` is also considered empty here
        return parent::isValueEmpty($value, $element) || $value === false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Allow null value to represent proper empty state
        return ($value === null) ? null : (bool)$value;
    }

    public function getDescriptionHtml(): Markup
    {
        // Allow the HTML to be overridden in templates with `setFieldSettings()`.
        if (!$this->_descriptionHtml) {
            $this->_descriptionHtml = $this->_getHtmlContent($this->description);
        }

        return Template::raw(Craft::t('formie', (string)$this->_descriptionHtml));
    }

    public function setDescriptionHtml($value): void
    {
        $this->_descriptionHtml = $value;
    }

    public function getDefaultState(): ?string
    {
        // An alias for `defaultValue` for GQL, as `defaultValue` returns a boolean, not string
        return $this->defaultValue;
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/agree/preview', [
            'field' => $this,
        ]);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'checkedValue' => [
                'name' => 'checkedValue',
                'type' => Type::string(),
            ],
            'uncheckedValue' => [
                'name' => 'uncheckedValue',
                'type' => Type::string(),
            ],
            // We're forced to use a string-representation of the default value, due to the parent `defaultValue` definition
            // So cast it properly here as a string, but also provide `defaultState` as the proper type.
            'defaultValue' => [
                'name' => 'defaultValue',
                'type' => Type::string(),
                'resolve' => function($field) {
                    return (string)$field->defaultValue;
                },
            ],
            'defaultState' => [
                'name' => 'defaultState',
                'type' => Type::boolean(),
            ],
            'descriptionHtml' => [
                'name' => 'descriptionHtml',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Description'),
                'help' => Craft::t('formie', 'The description for the field. This will be shown next to the checkbox.'),
                'name' => 'description',
                'validation' => 'requiredRichText',
                'required' => true,
            ], RichTextHelper::getRichTextConfig('fields.agree'))),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Checked Value'),
                'help' => Craft::t('formie', 'The value of this field when it is checked.'),
                'name' => 'checkedValue',
                'validation' => 'required',
                'required' => true,
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Unchecked Value'),
                'help' => Craft::t('formie', 'The value of this field when it is unchecked.'),
                'name' => 'uncheckedValue',
                'validation' => 'required',
                'required' => true,
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'The default value for the field when it loads.'),
                'name' => 'defaultValue',
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);

        if ($key === 'fieldContainer') {
            return new HtmlTag('fieldset', [
                'class' => [
                    'fui-fieldset',
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'field-label' => true,
                    'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ],
            ]);
        }

        if ($key === 'fieldOption') {
            return new HtmlTag('div', [
                'class' => 'fui-checkbox',
            ]);
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'checkbox',
                'id' => $id,
                'class' => 'fui-input fui-checkbox-input',
                'name' => $this->getHtmlName(),
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $this->getHtmlDataId($form),
                    'fui-input-type' => 'agree',
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
            ], $this->getInputAttributes());
        }

        if ($key === 'fieldOptionLabel') {
            return new HtmlTag('label', [
                'class' => 'fui-checkbox-label',
                'for' => $id,
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/agree/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return ($value) ? $this->checkedValue : $this->uncheckedValue;
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // If we require a boolean, return that
        if ($integrationField->getType() === IntegrationField::TYPE_BOOLEAN) {
            return (bool)$value;
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element);
    }


    // Private Methods
    // =========================================================================

    private function _getHtmlContent($content): ?string
    {
        // Sanity check for potentially bad settings
        $nodeType = $content[0]['type'] ?? null;

        if (!$nodeType) {
            return null;
        }

        return RichTextHelper::getHtmlContent($content);
    }
}
