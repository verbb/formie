<?php
namespace verbb\formie\fields;

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
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\fields\BaseRelationField;
use craft\fields\Categories as CraftCategories;
use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use craft\services\Gql as GqlService;

use GraphQL\Type\Definition\Type;

class Categories extends ElementField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Categories');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/categories/icon.svg';
    }

    public static function elementType(): string
    {
        return Category::class;
    }


    // Properties
    // =========================================================================

    public ?array $rootCategory = null;
    public bool $showStructure = false;


    // Public Methods
    // =========================================================================

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['placeholder'] = Craft::t('formie', 'Select a category');

        return $settings;
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories')]) : false,
        ];
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/categories/preview', [
            'field' => $this,
        ]);
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
            $options[] = ['label' => $prefix . $this->getElementLabel($element), 'value' => (string)$element->id, 'level' => $level];
        }

        return $options;
    }

    public function getRootCategoryElement(): array|Category|null
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

    public function getElementsQuery(): ElementQueryInterface
    {
        $query = parent::getElementsQuery();

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

        return $query;
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
        return array_merge(parent::getSettingGqlTypes(), [
            'defaultCategory' => [
                'name' => 'defaultCategory',
                'type' => CategoryInterface::getType(),
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
                'args' => CategoryArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getRootCategoryElement();
                },
            ],
        ]);
    }

    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(CategoryInterface::getType())),
            'args' => CategoryArguments::getArguments(),
            'resolve' => CategoryResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
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
                    ['value' => 'groups', 'label' => Craft::t('formie', 'Groups')],
                    ['value' => 'elements', 'label' => Craft::t('formie', 'Specific Elements')],
                ],
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which source do you want to select categories from?'),
                'name' => 'sources',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'if' => '$get(sourceType).value == groups',
                'showAllOption' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select categories from?'),
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
                'help' => Craft::t('formie', 'Select a default category to be selected.'),
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
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Root Category'),
                'help' => Craft::t('formie', 'Select a root category to only output the direct children of the chosen category. Leave empty to use the top-level category.'),
                'name' => 'rootCategory',
                'selectionLabel' => Craft::t('formie', 'Select a category'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
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
                'options' => $this->getLabelSourceOptions(),
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
