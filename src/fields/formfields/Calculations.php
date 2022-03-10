<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\SubfieldInterface;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Html;

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
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
            SchemaHelper::matchField([
                'fieldTypes' => [self::class],
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
                    'name' => Html::namespaceInputName($this->_getInputName($inputNames), $field->namespace),
                    'type' => get_class($field),
                ];
            }

            $inputNames[] = $fieldHandle;

            return [
                'name' => Html::namespaceInputName($this->_getInputName($inputNames), $field->namespace),
                'type' => get_class($field),
            ];
        }

        return null;
    }

    private function _getInputName($names)
    {
        $first = array_shift($names);

        if ($names) {
            return $first . '[' . implode('][', $names) . ']';
        }

        return $first ?? '';
    }
}
