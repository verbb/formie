<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Json;

use GraphQL\Type\Definition\Type;

class Calculations extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Calculations');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/calculations/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?array $formula = [];
    public string $formatting = '';
    public string $prefix = '';
    public string $suffix = '';
    public int $decimals = 0;

    private ?array $_renderedFormula = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/calculations/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/calculations/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/calculations.js', true),
            'module' => 'FormieCalculations',
            'settings' => [
                'formula' => $this->getFormula(),
                'formatting' => $this->formatting,
                'prefix' => $this->prefix,
                'suffix' => $this->suffix,
                'decimals' => $this->decimals,
            ],
        ];
    }

    public function getFormula(): array
    {
        if ($this->_renderedFormula) {
            return $this->_renderedFormula;
        }

        // Take the tiptap-stored formula and turn it into something JS will understand.
        $formula = RichTextHelper::getHtmlContent($this->formula);

        // Dissallow tags
        $formula = strip_tags($formula);

        // Grab all the variables used in the formula
        $variables = [];

        // Extract the field handles from a formula
        preg_match_all('/{field\.(.*?[^}])}/m', $formula, $matches);

        // `$keys` will be `{field.handle}`, `$values` will be `handle`.
        $keys = $matches[0] ?? [];
        $values = $matches[1] ?? [];
        $handles = array_combine($keys, $values);

        // Go through each field and make sure to namespace it for DOM lookup
        foreach ($handles as $handle) {
            $newHandle = 'field_' . str_replace('.', '_', $handle);

            $variables[$newHandle] = $this->_getFieldVariable($handle);
        }

        // Replace `{field.handle.sub}` with `field_handle_sub` to save any potential collisions with keywords
        // and because some characters won't work well with the expressionLanguage parser
        $formula = str_replace(['.', '{', '}'], ['_', '', ''], $formula);

        return $this->_renderedFormula = [
            'formula' => $formula,
            'variables' => $variables,
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'formula' => [
                'name' => 'formula',
                'type' => Type::string(),
                'resolve' => function($field) {
                    return (string)Json::encode($field->getFormula());
                },
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Calculations Formula'),
                'help' => Craft::t('formie', 'Provide the formula used to calculate the result for this field. Use arithmetic operators (`+`, `-`, `*`, `/`, etc) and reference other fields.'),
                'name' => 'formula',
                'variables' => 'calculationsVariables',
            ], RichTextHelper::getRichTextConfig('fields.calculations'))),
        ];
    }

    /**
     * @inheritDoc
     */
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
            SchemaHelper::matchField([
                'fieldTypes' => [self::class],
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Formatting'),
                'help' => Craft::t('formie', 'Select how to format the value calculated for this field.'),
                'name' => 'formatting',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Prefix'),
                'help' => Craft::t('formie', 'Add a prefix to the number.'),
                'name' => 'prefix',
                'if' => '$get(formatting).value == number',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Suffix'),
                'help' => Craft::t('formie', 'Add a suffix to the number.'),
                'name' => 'suffix',
                'if' => '$get(formatting).value == number',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Decimal Rounding'),
                'help' => Craft::t('formie', 'How many decimals to round the number to.'),
                'name' => 'decimals',
                'if' => '$get(formatting).value == number',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
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
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'text',
                'id' => $id,
                'class' => 'fui-input',
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'readonly' => true,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }
        
        return parent::defineHtmlTag($key, $context);
    }


    // Private Methods
    // =========================================================================

    private function _getFieldVariable($fieldKey, $element = null, $inputNames = []): ?array
    {
        // Check for nested field handles
        if (str_contains($fieldKey, '.')) {
            $fieldKey = explode('.', $fieldKey);
            $fieldHandle = array_shift($fieldKey);
            $fieldKey = implode('.', $fieldKey);
        } else {
            $fieldHandle = $fieldKey;
            $fieldKey = '';
        }

        if (!$element) {
            $element = $this->getForm();
        }

        if ($field = $element->getFieldByHandle($fieldHandle)) {
            if ($field instanceof Group) {
                $inputNames = array_merge($inputNames, [$fieldHandle, 'rows', 'new1', 'fields']);

                return $this->_getFieldVariable($fieldKey, $field, $inputNames);
            }

            if ($field instanceof SubfieldInterface) {
                $inputNames = array_merge($inputNames, [$fieldHandle, $fieldKey]);

                return [
                    'handle' => Html::getInputNameAttribute($inputNames),
                    'name' => Html::namespaceInputName(Html::getInputNameAttribute($inputNames), $field->namespace),
                    'type' => get_class($field),
                ];
            }

            $inputNames[] = $fieldHandle;

            return [
                'handle' => Html::getInputNameAttribute($inputNames),
                'name' => Html::namespaceInputName(Html::getInputNameAttribute($inputNames), $field->namespace),
                'type' => get_class($field),
            ];
        }

        return null;
    }
}
