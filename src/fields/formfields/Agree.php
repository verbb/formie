<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\prosemirror\tohtml\Renderer as HtmlRenderer;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Json;
use craft\helpers\Template;

use yii\db\Schema;
use GraphQL\Type\Definition\Type;

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

    public $description;
    public $checkedValue;
    public $uncheckedValue;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_BOOLEAN;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        return !!$value;
    }

    /**
     * @inheritDoc
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        // Default to yii\validators\Validator::isEmpty()'s behavior
        return $value === null || $value === [] || $value === '' || $value === false;
    }

    /**
     * @inheritDoc
     */
    public function getDescriptionHtml()
    {
        $html = $this->_getHtmlContent($this->description);

        return Template::raw(Craft::t('formie', $html));
    }

    /**
     * @inheritDoc
     */
    public function getDefaultState()
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
    public function getInputHtml($value, ElementInterface $element = null): string
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

    /**
     * @inheritDoc
     */
    public function getSettingGqlTypes()
    {
        return array_merge(parent::getSettingGqlTypes(), [
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
                'help' => Craft::t('formie', 'he value of this field when it is unchecked.'),
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
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
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
            SchemaHelper::cssClasses(),
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


    // Protected Methods
    // =========================================================================
      
    /**
     * @inheritDoc
     */
    protected function getSettingGqlType($attribute, $type, $fieldInfo)
    {
        // Disable normal `defaultValue` as it is a boolean, not string. We can't have the same attributes 
        // return multiple types. Instead, return `defaultState` as the attribute name and correct type.
        if ($attribute === 'defaultValue') {
            return [
                'name' => 'defaultState',
                'type' => Type::boolean(),
            ];
        }

        return parent::getSettingGqlType($attribute, $type, $fieldInfo);
    }


    // Private Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    private function _getHtmlContent($content)
    {
        if (is_string($content)) {
            $content = Json::decodeIfJson($content);
        }

        $renderer = new HtmlRenderer();

        $html = $renderer->render([
            'type' => 'doc',
            'content' => $content,
        ]);

        // Strip out paragraphs, replace with `<br>`
        $html = str_replace(['<p>', '</p>'], ['', '<br>'], $html);
        $html = preg_replace('/(<br>)+$/', '', $html);

        // Prosemirror will use `htmlentities` for special characters, but doesn't play nice
        // with static translations. Convert them back.
        $html = html_entity_decode($html);

        return $html;
    }
}
