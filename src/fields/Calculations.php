<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\base\SubFieldInterface;
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

class Calculations extends Field implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Calculations');
    }

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

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/calculations/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/calculations.js'),
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

        // Grab all the variables used in the formula
        $variables = [];

        // Extract the field handles from a formula
        preg_match_all('/{field:(.*?[^}])}/m', $formula, $matches);

        // `$keys` will be `{field:handle}`, `$values` will be `handle`.
        $keys = $matches[0] ?? [];
        $values = $matches[1] ?? [];
        $handles = array_combine($keys, $values);

        // Go through each field and make sure to namespace it for DOM lookup
        foreach ($handles as $handle) {
            $newHandle = 'field_' . str_replace('.', '_', $handle);

            $variables[$newHandle] = $this->_getFieldVariable($handle);
        }

        // Replace `{field.handle.sub}` with `field_handle_sub` to save any potential collisions with keywords
        // and because some characters won't work well with the expressionLanguage parser. It's important not to
        // replace `.` characters outside of this string of course
        $formula = preg_replace_callback('/({.*?})/', function($matches) {
            $string = $matches[1] ?? '';
            return str_replace(['.', ':', '{', '}'], ['_', '_', '', ''], $string);
        }, $formula);

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
                    return Json::encode($field->getFormula());
                },
            ],
            'formatting' => [
                'name' => 'formatting',
                'type' => Type::string(),
            ],
            'prefix' => [
                'name' => 'prefix',
                'type' => Type::string(),
            ],
            'suffix' => [
                'name' => 'suffix',
                'type' => Type::string(),
            ],
            'decimals' => [
                'name' => 'decimals',
                'type' => Type::int(),
            ],
        ]);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Calculations Formula'),
                'help' => Craft::t('formie', 'Provide the formula used to calculate the result for this field. Use arithmetic operators (`+`, `-`, `*`, `/`, etc) and reference other fields.'),
                'name' => 'formula',
                'variables' => 'calculationsVariables',
                'disable-paste-rules' => true,
                'disable-input-rules' => true,
            ], RichTextHelper::getRichTextConfig('fields.calculations'))),
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
            SchemaHelper::includeInEmailField(),
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
            return new HtmlTag('input', [
                'type' => 'text',
                'id' => $id,
                'class' => 'fui-input',
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'readonly' => true,
                'data' => [
                    'fui-id' => $dataId,
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }
        
        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/calculations/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getFieldVariable(string $fieldKey, mixed $element = null): ?array
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
            $namespace = Html::getInputNameAttribute($field->getFullNamespace());

            if ($field instanceof SingleNestedFieldInterface) {
                return $this->_getFieldVariable($fieldKey, $field);
            }

            if ($field instanceof Checkboxes) {
                return [
                    'handle' => $fieldHandle,
                    'name' => Html::namespaceInputName($fieldHandle, $namespace) . '[]',
                    'type' => get_class($field),
                ];
            }

            return [
                'handle' => $fieldHandle,
                'name' => Html::namespaceInputName($fieldHandle, $namespace),
                'type' => get_class($field),
            ];
        }

        return null;
    }
}
