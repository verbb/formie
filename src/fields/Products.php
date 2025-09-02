<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ElementField;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\services\Gql as GqlService;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Product;
use craft\commerce\fields\Products as CommerceProducts;
use craft\commerce\gql\arguments\elements\Product as ProductArguments;
use craft\commerce\gql\interfaces\elements\Product as ProductInterface;
use craft\commerce\gql\resolvers\elements\Product as ProductResolver;

use GraphQL\Type\Definition\Type;

// Prevent a fatal error if the Commerce class doesn't exist. This is because fields are used
// at very low-level Craft calls, which can mess things up when encountered.
if (!class_exists(CommerceProducts::class)) {
    return;
}

class Products extends ElementField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Products');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/products/icon.svg';
    }

    public static function elementType(): string
    {
        return Product::class;
    }

    public static function getRequiredPlugins(): array
    {
        return [
            ['handle' => 'commerce', 'version' => '4.0.0'],
        ];
    }
    

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Enforce any required plugin before creating the field, but not before Craft is ready
        Craft::$app->onInit(function() {
            Formie::$plugin->getFields()->checkRequiredPlugin($this);
        });

        parent::init();
    }

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['placeholder'] = Craft::t('formie', 'Select a product');

        return $settings;
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 0 ? Craft::t('formie', 'No product types available. View [product type settings]({link}).', ['link' => UrlHelper::cpUrl('commerce/settings/producttypes')]) : false,
        ];
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/products/preview', [
            'field' => $this,
        ]);
    }

    public function defineLabelSourceOptions(): array
    {
        $options = [
            ['value' => 'title', 'label' => Craft::t('app', 'Title')],
            ['value' => 'defaultSku', 'label' => Craft::t('app', 'SKU')],
            ['value' => 'defaultPrice', 'label' => Craft::t('app', 'Price')],
            ['value' => 'defaultHeight', 'label' => Craft::t('app', 'Height')],
            ['value' => 'defaultLength', 'label' => Craft::t('app', 'Length')],
            ['value' => 'defaultWidth', 'label' => Craft::t('app', 'Width')],
            ['value' => 'defaultWeight', 'label' => Craft::t('app', 'Weight')],
            ['value' => 'postDate', 'label' => Craft::t('app', 'Post Date')],
            ['value' => 'expiryDate', 'label' => Craft::t('app', 'Expiry Date')],
        ];

        $extraOptions = [];

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $typeId = $source['criteria']['typeId'] ?? null;

                if ($typeId && !is_array($typeId)) {
                    $productType = Commerce::getInstance()->getProductTypes()->getProductTypeById($typeId);

                    $fields = $this->getStringCustomFieldOptions($productType->getCustomFields());

                    $extraOptions[] = $fields;
                }
            }
        }

        return array_merge($options, ...$extraOptions);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'defaultProduct' => [
                'name' => 'defaultProduct',
                'type' => ProductInterface::getType(),
                'args' => ProductArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getDefaultValueQuery() ? $class->getDefaultValueQuery()->one() : null;
                },
            ],
            'products' => [
                'name' => 'products',
                'type' => Type::listOf(ProductInterface::getType()),
                'args' => ProductArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getElementsQuery()->all();
                },
            ],
        ]);
    }

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
                'if' => '$get(displayType).value == dropdown',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Source Type'),
                'help' => Craft::t('formie', 'Select what source type to use for this field.'),
                'name' => 'sourceType',
                'options' => [
                    ['value' => 'groups', 'label' => Craft::t('formie', 'Product Types')],
                    ['value' => 'elements', 'label' => Craft::t('formie', 'Specific Elements')],
                ],
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which source do you want to select products from?'),
                'name' => 'sources',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'if' => '$get(sourceType).value == groups',
                'showAllOption' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No product types available. View [product type settings]({link}).', ['link' => UrlHelper::cpUrl('commerce/settings/producttypes')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select products from?'),
                'name' => 'sourceElements',
                'validation' => 'required',
                'required' => true,
                'if' => '$get(sourceType).value == elements',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => static::elementType(),
                    'limit' => 999,
                ],
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default product to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => static::elementType(),
                ],
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
            SchemaHelper::emailNotificationValue(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable products.'),
                'name' => 'limit',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'help' => Craft::t('formie', 'Limit the number of available products.'),
                'name' => 'limitOptions',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'help' => Craft::t('formie', 'Select what to use as the label for each product.'),
                'name' => 'labelSource',
                'options' => $this->getLabelSourceOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Options Order'),
                'help' => Craft::t('formie', 'Select what order to show products by.'),
                'name' => 'orderBy',
                'options' => $this->getOrderByOptions(),
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes'],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Multiple'),
                'help' => Craft::t('formie', 'Whether this field should allow multiple options to be selected.'),
                'name' => 'multi',
                'if' => '$get(displayType).value == dropdown',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Layout'),
                'help' => Craft::t('formie', 'Select which layout to use for these fields.'),
                'name' => 'layout',
                'if' => '$get(displayType).value != dropdown',
                'options' => [
                    ['label' => Craft::t('formie', 'Vertical'), 'value' => 'vertical'],
                    ['label' => Craft::t('formie', 'Horizontal'), 'value' => 'horizontal'],
                ],
            ]),
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
}
