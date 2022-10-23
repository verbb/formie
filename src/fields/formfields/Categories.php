<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\fields\BaseRelationField;
use craft\fields\Categories as CraftCategories;
use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;

use GraphQL\Type\Definition\Type;

class Categories extends CraftCategories implements FormFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ELEMENT_QUERY = 'modifyElementQuery';


    // Traits
    // =========================================================================

    use FormFieldTrait, RelationFieldTrait {
        getDefaultValue as traitGetDefaultValue;
        getFrontEndInputOptions as traitGetFrontendInputOptions;
        getEmailHtml as traitGetEmailHtml;
        getSavedFieldConfig as traitGetSavedFieldConfig;
        getSettingGqlTypes as traitGetSettingGqlTypes;
        defineHtmlTag as traitDefineHtmlTag;
        RelationFieldTrait::defineValueAsString insteadof FormFieldTrait;
        RelationFieldTrait::defineValueAsJson insteadof FormFieldTrait;
        RelationFieldTrait::defineValueForIntegration insteadof FormFieldTrait;
        RelationFieldTrait::populateValue insteadof FormFieldTrait;
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

    public bool $searchable = true;
    public ?array $rootCategory = null;
    public bool $showStructure = false;

    protected string $inputTemplate = 'formie/_includes/element-select-input';

    private ?CategoryGroup $_categoryGroup = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        // The default Craft Categories field behaviour is pretty odd. It'll select all child categories in the same branch
        // which is completely not what we want. We just want to save the categories we pick - is that too much to ask?!
        // Bubble up this method to the base `normalizeValue()` to skip the `CraftCategories::normalizeValue()`.
        return BaseRelationField::normalizeValue($value, $element);
    }

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
            'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories')]) : false,
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
            'labelSource' => 'title',
            'orderBy' => 'title ASC',
        ];
    }

    public function getDefaultValue($attributePrefix = '')
    {
        // If the default value from the parent field (query params, etc.) is empty, use the default values
        // set in the field settings.
        $this->defaultValue = $this->traitGetDefaultValue($attributePrefix) ?? $this->defaultValue;

        return $this->getDefaultValueQuery();
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/categories/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $renderOptions);

        // TODO: replace with `elementsQuery` at next breakpoint
        $inputOptions['categoriesQuery'] = $this->getElementsQuery();
        $inputOptions['elementsQuery'] = $this->getElementsQuery();

        $inputOptions['isMultiLevel'] = $this->getIsMultiLevel();
        $inputOptions['allowMultiple'] = $this->branchLimit > 1;

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        // Ensure we return the correct, prepped query for emails. Just as we would be submissions.
        $value = $this->_all($value, $submission);

        return $this->traitGetEmailHtml($submission, $notification, $value, $renderOptions);
    }

    public function getFieldOptions(): array
    {
        $options = [];

        foreach ($this->getElementsQuery()->all() as $element) {
            // Negate the first level to start at 0, so no "-" character is shown at first level
            $level = $element->level - 1;

            // Reset the level based off the root category. Otherwise, the level will start from the root
            // category's level, not "flush" (ie, "---- Category" instead of "- Category")
            if ($rootCategory = $this->getRootCategoryElement()) {
                $level = $level - $rootCategory->level;
            }

            // Prefix if showing structure info
            $prefix = '';

            if ($this->showStructure && $level > 0) {
                $prefix = str_repeat('-', $level) . ' ';
            }

            // Important to cast as a string, otherwise Twig will struggle to compare
            $options[] = ['label' => $prefix . $this->_getElementLabel($element), 'value' => (string)$element->id, 'level' => $level];
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function getRootCategoryElement()
    {
        if ($this->rootCategory) {
            if ($rootCategoryId = ArrayHelper::getColumn($this->rootCategory, 'id')) {
                if ($rootCategory = Category::find()->id($rootCategoryId)->one()) {
                    return $rootCategory;
                }
            }
        }

        return null;
    }

    /**
     * Returns the list of selectable categories.
     *
     * @return ElementQueryInterface
     * @throws SiteNotFoundException
     */
    public function getElementsQuery(): ElementQueryInterface
    {
        $query = Category::find();

        if ($this->source !== '*') {
            // Try to find the criteria we're restricting by - if any
            $elementSource = ArrayHelper::firstWhere($this->availableSources(), 'key', $this->source);
            $criteria = $elementSource['criteria'] ?? [];

            // Apply the criteria on our query
            Craft::configure($query, $criteria);

            // Handle conditions by parsing the rules and applying to query
            $conditionRules = $elementSource['condition']['conditionRules'] ?? [];

            foreach ($conditionRules as $conditionRule) {
                $rule = Craft::createObject($conditionRule);
                $rule->modifyQuery($query);
            }
        }

        // Restrict elements to be on the current site, for multi-sites
        if (Craft::$app->getIsMultiSite()) {
            $query->siteId(Craft::$app->getSites()->getCurrentSite()->id);
        }

        // Check if a default value has been set AND we're limiting. We need to resolve the value before limiting
        if ($this->defaultValue && $this->limitOptions) {
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

        // If the root category is selected
        if ($this->rootCategory) {
            // Handle the two ways a default value can be set
            if ($this->rootCategory instanceof ElementQueryInterface) {
                $ids = $this->rootCategory->id;
            } else {
                $ids = ArrayHelper::getValue($this->rootCategory, '0.id');
            }

            if ($ids) {
                $query->descendantOf($ids);
            }
        }

        $query->limit($this->limitOptions);
        $query->orderBy($this->orderBy);

        // Allow any template-defined elementQuery to override
        if ($this->elementsQuery) {
            Craft::configure($query, $this->elementsQuery);
        }

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

    public function defineLabelSourceOptions(): array
    {
        $options = [
            ['value' => 'title', 'label' => Craft::t('app', 'Title')],
            ['value' => 'slug', 'label' => Craft::t('app', 'Slug')],
            ['value' => 'uri', 'label' => Craft::t('app', 'URI')],
        ];

        $extraOptions = [];

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $groupId = $source['criteria']['groupId'] ?? null;

                if ($groupId && !is_array($groupId)) {
                    $group = Craft::$app->getCategories()->getGroupById($groupId);

                    if ($group) {
                        $fields = $this->getStringCustomFieldOptions($group->getCustomFields());

                        $extraOptions[] = $fields;
                    }
                }
            }
        }

        return array_merge($options, ...$extraOptions);
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge($this->traitGetSettingGqlTypes(), [
            'defaultValue' => [
                'name' => 'defaultValue',
                'type' => Type::string(),
                'resolve' => function($field) {
                    $value = $field->defaultValue;

                    return is_array($value) ? Json::encode($value) : $value;
                },
            ],
            'defaultCategory' => [
                'name' => 'defaultCategory',
                'type' => CategoryInterface::getType(),
                'resolve' => CategoryResolver::class.'::resolve',
                'args' => CategoryArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getDefaultValueQuery() ? $class->getDefaultValueQuery()->one() : null;
                },
            ],
            'categories' => [
                'name' => 'categories',
                'type' => Type::listOf(CategoryInterface::getType()),
                'args' => CategoryArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getElementsQuery()->all();
                },
            ],
            'rootCategory' => [
                'name' => 'rootCategory',
                'type' => CategoryInterface::getType(),
                'resolve' => CategoryResolver::class . '::resolve',
                'args' => CategoryArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getRootCategoryElement();
                },
            ],
        ]);
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
                'if' => '$get(displayType).value == dropdown',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Source'),
                'help' => Craft::t('formie', 'Which source do you want to select categories from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default category to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => self::defaultSelectionLabel(),
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Branch Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable category branches.'),
                'name' => 'branchLimit',
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Root Category'),
                'help' => Craft::t('formie', 'Select a root category to only output the direct children of the chosen category. Leave empty to use the top-level category.'),
                'name' => 'rootCategory',
                'selectionLabel' => Craft::t('formie', 'Select a category'),
                'config' => [
                    'jsClass' => $this->inputJsClass,
                    'elementType' => static::elementType(),
                ],
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'help' => Craft::t('formie', 'Limit the number of available categories.'),
                'name' => 'limitOptions',
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
                'options' => array_merge([
                    ['value' => 'lft ASC', 'label' => 'Structure Ascending'],
                    ['value' => 'lft DESC', 'label' => 'Structure Descending'],
                ], $this->getOrderByOptions()),
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
                'label' => Craft::t('formie', 'Show Structure'),
                'help' => Craft::t('formie', 'Whether to show the hierarchical structure of categories in the dropdown.'),
                'name' => 'showStructure',
                'if' => '$get(displayType).value == dropdown',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Multiple'),
                'help' => Craft::t('formie', 'Whether this field should allow multiple options to be selected.'),
                'name' => 'multiple',
                'if' => '$get(displayType).value == dropdown',
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

        if (in_array($this->displayType, ['checkboxes', 'radio'])) {
            if ($key === 'fieldContainer') {
                return new HtmlTag('fieldset', [
                    'class' => 'fui-fieldset',
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ]);
            }

            if ($key === 'fieldLabel') {
                return new HtmlTag('legend', [
                    'class' => 'fui-legend',
                ]);
            }
        }

        return $this->traitDefineHtmlTag($key, $context);
    }
}
