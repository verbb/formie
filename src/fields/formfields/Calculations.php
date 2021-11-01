<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\VariableNode;
use verbb\formie\prosemirror\tohtml\Renderer;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;

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

    public $formula;

    private $_renderedFormula;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
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
            'field' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules()
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/calculations.js', true),
            'module' => 'FormieCalculations',
            'settings' => [
                'formula' => $this->getFormula(),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFormula()
    {
        if ($this->_renderedFormula) {
            return $this->_renderedFormula;
        }

        // Take the tiptap-stored formula and turn it into something JS will understand.
        $content = Json::decode($this->formula);

        $renderer = new Renderer();
        $renderer->addNode(VariableNode::class);

        $formula = $renderer->render([
            'type' => 'doc',
            'content' => $content,
        ]);

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

        // Go through each field and make sure we namespace it for DOM lookup
        foreach ($handles as $key => $handle) {
            if ($field = $this->getForm()->getFieldByHandle($handle)) {
                $name = Html::namespaceInputName($handle, $field->namespace);
                
                $variables['field_' . $handle] = [
                    'name' => $name,
                    'type' => get_class($field),
                ];
            }
        }

        // Replace `{field.handle}` with `field_handle`
        $formula = preg_replace('/{field\.(.*?[^}])}/m', 'field_$1', $formula);

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

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}
