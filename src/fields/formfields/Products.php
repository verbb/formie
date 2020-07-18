<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\UrlHelper;
use craft\commerce\elements\Product;
use craft\commerce\Plugin;
use craft\elements\db\ElementQueryInterface;
use craft\commerce\fields\Products as CommerceProducts;

class Products extends CommerceProducts implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        getFrontendInputOptions as traitGetFrontendInputOptions;
    }
    use RelationFieldTrait;


    // Properties
    // =========================================================================

    protected $inputTemplate = 'formie/_includes/elementSelect';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Products');
    }

    /**
     * @inheritDoc
     */
    public static function getTemplatePath(): string
    {
        return 'fields/products';
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/products/icon.svg';
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        $options = $this->getSourceOptions();
        
        return [
            'sourceOptions' => $options,
            'warning' => count($options) < 2 ? Craft::t('formie', 'No product types available. View [product type settings]({link}).', ['link' => UrlHelper::cpUrl('commerce/settings/producttypes') ]) : false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'sources' => '*',
            'placeholder' => Craft::t('formie', 'Select a product'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/products/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontendInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $options);
        $inputOptions['productsQuery'] = $this->getProductsQuery();

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml($value, $showName = true)
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/products/email', [
            'field' => $this,
            'value' => $value,
            'showName' => $showName,
        ]);
    }

    /**
     * Returns the list of selectable products.
     *
     * @return Product[]
     */
    public function getProductsQuery()
    {
        $query = Product::find();

        if ($this->sources !== '*') {
            $typeHandles = [];

            foreach ($this->sources as $source) {
                list(, $uid) = explode(':', $source);

                if ($type = Plugin::getInstance()->getProductTypes()->getProductTypeByUid($uid)) {
                    $typeHandles[] = $type->handle;
                }
            }

            if (empty($typeHandles)) {
                return [];
            }

            $query->type($typeHandles);
        }

        return $query->orderBy('title ASC');
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        $options = $this->getSourceOptions();
        
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The option shown initially, when no option is selected.'),
                'name' => 'placeholder',
                'validation' => 'required',
                'required' => true,
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select products from?'),
                'name' => 'sources',
                'showAllOption' => true,
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No product types available. View [product type settings]({link}).', ['link' => UrlHelper::cpUrl('commerce/settings/producttypes') ]) : false,
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable products.'),
                'name' => 'limit',
                'size' => '3',
                'class' => 'text',
                'validation' => 'optional|number|min:0',
            ]),
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
            SchemaHelper::inputAttributesField(),
        ];
    }
}
