<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\HtmlTag;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Template;

use yii\db\Schema;

use GraphQL\Type\Definition\Type;

use Twig\Markup;

class Agree extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Agree');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/agree/icon.svg';
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

    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
        // Moved to a private variable in 2.0.3 due to infinite loop issue. TODO: remove at next breakpoint
        if (array_key_exists('descriptionHtml', $config)) {
            unset($config['descriptionHtml']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function attributes()
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

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_BOOLEAN;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        return (bool)$value;
    }

    /**
     * @inheritDoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        // Default to yii\validators\Validator::isEmpty()'s behavior
        return $value === null || $value === [] || $value === '' || $value === false;
    }

    public function getDescriptionHtml(): Markup
    {
        // Allow the HTML to be overridden in templates with `setFieldSettings()`.
        if (!$this->_descriptionHtml) {
            $this->_descriptionHtml = $this->_getHtmlContent($this->description);
        }

        return Template::raw(Craft::t('formie', (string)$this->_descriptionHtml));
    }

    public function setDescriptionHtml($value)
    {
        $this->_descriptionHtml = $value;
    }

    public function getDefaultState(): ?string
    {
        // An alias for `defaultValue` for GQL, as `defaultValue` returns a boolean, not string
        return $this->defaultValue;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'defaultValue' => false,
            'labelPosition' => HiddenPosition::class,
            'checkedValue' => Craft::t('app', 'Yes'),
            'uncheckedValue' => Craft::t('app', 'No'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/agree/input', [
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/agree/preview', [
            'field' => $this,
        ]);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            // We're force to use a string-representation of the default value, due to the parent `defaultValue` definition
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

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::richTextField(array_merge([
                'label' => Craft::t('formie', 'Description'),
                'help' => Craft::t('formie', 'The description for the field. This will be shown next to the checkbox.'),
                'name' => 'description',
                'validation' => 'required',
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
            SchemaHelper::prePopulate(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
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

        if ($key === 'fieldOption') {
            return new HtmlTag('div', [
                'class' => 'fui-checkbox',
            ]);
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'checkbox',
                'id' => $id,
                'class' => 'fui-input fui-checkbox-input',
                'name' => $this->getHtmlName(),
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $this->getHtmlDataId($form),
                    'fui-input-type' => 'agree',
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
            ], $this->getInputAttributes()));
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

    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        return ($value) ? $this->checkedValue : $this->uncheckedValue;
    }

    protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
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
