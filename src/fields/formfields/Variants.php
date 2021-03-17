<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Variant;
use craft\commerce\models\ProductType;
use craft\commerce\fields\Variants as CommerceVariants;

class Variants extends CommerceVariants implements FormFieldInterface
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_ELEMENT_QUERY = 'modifyElementQuery';


    // Traits
    // =========================================================================

    use FormFieldTrait, RelationFieldTrait {
        getFrontEndInputOptions as traitGetFrontendInputOptions;
        getEmailHtml as traitGetEmailHtml;
        getSavedFieldConfig as traitGetSavedFieldConfig;
        RelationFieldTrait::getIsFieldset insteadof FormFieldTrait;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Variants');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/variants/icon.svg';
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $searchable = true;

    /**
     * @var string
     */
    protected $inputTemplate = 'formie/_includes/element-select-input';

    /**
     * @var ProductType
     */
    private $_productType;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getSavedFieldConfig(): array
    {
        $settings = $this->traitGetSavedFieldConfig();

        return $this->modifyFieldSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        $options = $this->getSourceOptions();

        return [
            'sourceOptions' => $options,
            'warning' => count($options) === 1 ? Craft::t('formie', 'No product types available. View [product type settings]({link}).', ['link' => UrlHelper::cpUrl('commerce/settings/producttypes') ]) : false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'source' => '*',
            'placeholder' => Craft::t('formie', 'Select a variant'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValue($attributePrefix = '')
    {
        return $this->getDefaultValueQuery();
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/variants/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $options);
        $inputOptions['variantsQuery'] = $this->getElementsQuery();

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        // Ensure we return back the correct, prepped query for emails. Just as we would be submissions.
        $value = $this->_all($value, $submission);

        return $this->traitGetEmailHtml($submission, $notification, $value, $options);
    }

    /**
     * Returns the list of selectable variants.
     *
     * @return Variant[]
     */
    public function getElementsQuery()
    {
        $query = Variant::find();

        if ($this->source !== '*') {
            // Try to find the criteria we're restricting by - if any
            $elementSource = ArrayHelper::firstWhere($this->availableSources(), 'key', $this->source);
            $criteria = $elementSource['criteria'] ?? [];

            // Apply the criteria on our query
            Craft::configure($query, $criteria);
        }

        // Restrict elements to be on the current site, for multi-sites
        if (Craft::$app->getIsMultiSite()) {
            $query->siteId(Craft::$app->getSites()->getCurrentSite()->id);
        }

        // Check if a default value has been set AND we're limiting. We need to resolve the value before limiting
        if ($this->defaultValue && $this->limit) {
            $ids = [];

            // Handle the two ways a default value can be set
            if ($this->defaultValue instanceof ElementQueryInterface) {
                $ids = $this->defaultValue->id;
            } else {
                $ids = ArrayHelper::getColumn($this->defaultValue, 'id');
            }
            
            if ($ids) {
                $query->id($ids);
            }
        }

        $query->limit($this->limit);
        $query->orderBy($this->orderBy);

        // Fire a 'modifyElementFieldQuery' event
        $event = new ModifyElementFieldQueryEvent([
            'query' => $query,
            'field' => $this,
        ]);
        $this->trigger(self::EVENT_MODIFY_ELEMENT_QUERY, $event);

        return $event->query;
    }

    /**
     * @inheritDoc
     */
    public function getSourceOptions(): array
    {
        $options = parent::getSourceOptions();

        return array_merge([['label' => Craft::t('formie', 'Select an option'), 'value' => '']], $options);
    }

    /**
     * @inheritDoc
     */
    public function defineLabelSourceOptions()
    {
        $options = [
            ['value' => 'title', 'label' => Craft::t('app', 'Title')],
            ['value' => 'sku', 'label' => Craft::t('app', 'SKU')],
            ['value' => 'price', 'label' => Craft::t('app', 'Price')],
            ['value' => 'height', 'label' => Craft::t('app', 'Height')],
            ['value' => 'length', 'label' => Craft::t('app', 'Length')],
            ['value' => 'width', 'label' => Craft::t('app', 'Width')],
            ['value' => 'weight', 'label' => Craft::t('app', 'Weight')],
        ];

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $typeId = $source['criteria']['typeId'] ?? null;

                if ($typeId && !is_array($typeId)) {
                    $productType = Commerce::getInstance()->getProductTypes()->getProductTypeById($typeId);

                    $fields = $this->getStringCustomFieldOptions($productType->getFields());

                    $options = array_merge($options, $fields);
                }
            }
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        $options = $this->getSourceOptions();

        return [
            SchemaHelper::labelField(),
            SchemaHelper::toggleContainer('settings.displayType=dropdown', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The option shown initially, when no option is selected.'),
                    'name' => 'placeholder',
                    'validation' => 'required',
                    'required' => true,
                ]),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Source'),
                'help' => Craft::t('formie', 'Which source do you want to select variants from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No product types available. View [product type settings]({link}).', ['link' => UrlHelper::cpUrl('commerce/settings/producttypes') ]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default variant to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => $this->defaultSelectionLabel(),
                'config' => [
                    'jsClass' => $this->inputJsClass,
                    'elementType' => static::elementType(),
                ],
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        $labelSourceOptions = $this->getLabelSourceOptions();
        
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
                'help' => Craft::t('formie', 'Limit the number of selectable variants.'),
                'name' => 'limit',
                'size' => '3',
                'class' => 'text',
                'validation' => 'optional|number|min:0',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'help' => Craft::t('formie', 'Select what to use as the label for each variant.'),
                'name' => 'labelSource',
                'options' => $labelSourceOptions,
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Options Order'),
                'help' => Craft::t('formie', 'Select what order to show variants by.'),
                'name' => 'orderBy',
                'options' => $this->getOrderByOptions(),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    [ 'label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown' ],
                    [ 'label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes' ],
                    [ 'label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio' ],
                ],
            ]),
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


    // Private Methods
    // =========================================================================

    /**
     * Returns the product type.
     *
     * @return ProductType|null
     */
    private function _getProductType()
    {
        if ($this->source === '*') {
            return null;
        }

        if (!$this->_productType && is_array($this->source)) {
            list(, $uid) = explode(':', $this->source);
            return Plugin::getInstance()->getProductTypes()->getProductTypeByUid($uid);
        }

        return $this->_productType;
    }
}
