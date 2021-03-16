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
use craft\elements\Category;
use craft\fields\Categories as CraftCategories;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;

use craft\models\CategoryGroup;

class Categories extends CraftCategories implements FormFieldInterface
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
        return Craft::t('formie', 'Categories');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/categories/icon.svg';
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
     * @var CategoryGroup
     */
    private $_categoryGroup;


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
            'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories') ]) : false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        $group = null;
        $groups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($groups)) {
            $group = 'group:' . $groups[0]->uid;
        }

        return [
            'source' => $group,
            'placeholder' => Craft::t('formie', 'Select a category'),
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/categories/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $options);
        $inputOptions['categoriesQuery'] = $this->getElementsQuery();
        $inputOptions['isMultiLevel'] = $this->getIsMultiLevel();
        $inputOptions['allowMultiple'] = $this->branchLimit > 1;

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
     * Returns the list of selectable categories.
     *
     * @return Category[]
     */
    public function getElementsQuery()
    {
        $query = Category::find();

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

        // Check if a default value has been set, and set it before limiting
        if ($this->defaultValue) {
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
     * Returns true if the categories have more than 1 level.
     *
     * @return bool
     */
    public function getIsMultiLevel(): bool
    {
        // $query = $this->getElementsQuery();

        // return $query->hasDescendants()->exists();
        
        return false;
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
            ['value' => 'slug', 'label' => Craft::t('app', 'Slug')],
            ['value' => 'uri', 'label' => Craft::t('app', 'URI')],
        ];

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $groupId = $source['criteria']['groupId'] ?? null;

                if ($groupId && !is_array($groupId)) {
                    $group = Craft::$app->getCategories()->getGroupById($groupId);

                    if ($group) {
                        $fields = $this->getStringCustomFieldOptions($group->getFields());

                        $options = array_merge($options, $fields);
                    }
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
                'help' => Craft::t('formie', 'Which source do you want to select categories from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories') ]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default category to be selected.'),
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
                'label' => Craft::t('formie', 'Branch Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable category branches.'),
                'name' => 'branchLimit',
                'size' => '3',
                'class' => 'text',
                'validation' => 'optional|number|min:0',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'help' => Craft::t('formie', 'Select what to use as the label for each category.'),
                'name' => 'labelSource',
                'options' => $labelSourceOptions,
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Options Order'),
                'help' => Craft::t('formie', 'Select what order to show categories by.'),
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
        ];
    }
}
