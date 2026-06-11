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
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\fields\Users as CraftUsers;
use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\UserGroup;
use craft\services\Gql as GqlService;

use GraphQL\Type\Definition\Type;

class Users extends ElementField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Users');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/users/icon.svg';
    }

    public static function elementType(): string
    {
        return User::class;
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return static::gqlElementContentTypeDefinitionFromConfig(
            $config,
            UserInterface::getType(),
            UserArguments::getArguments(),
            UserResolver::class,
        );
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return static::gqlElementContentMutationArgumentTypeDefinitionFromConfig($config);
    }

    protected static function defineOptionSource(): ?array
    {
        return [
            'handle' => 'users',
            'label' => static::displayName(),
        ];
    }


    // Properties
    // =========================================================================

    public string $labelSource = 'email';
    public string $orderBy = 'email ASC';


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['placeholder'] = $config['placeholder'] ?? Craft::t('formie', 'Select a user');

        parent::__construct($config);
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 0 ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users')]) : false,
        ];
    }

    public function getOptionSourceWarning(array $sourceOptions): ?string
    {
        return $sourceOptions === []
            ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users')])
            : null;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewElementField(),
        ];
    }

    public function getElementsQuery(): ElementQueryInterface
    {
        $query = parent::getElementsQuery();

        // Only return users that are active
        $query->status(User::STATUS_ACTIVE);

        return $query;
    }

    public function defineLabelSourceOptions(): array
    {
        $options = [
            ['value' => 'username', 'label' => Craft::t('app', 'Username')],
            ['value' => 'email', 'label' => Craft::t('app', 'Email')],
            ['value' => 'firstName', 'label' => Craft::t('app', 'First Name')],
            ['value' => 'lastName', 'label' => Craft::t('app', 'Last Name')],
            ['value' => 'fullName', 'label' => Craft::t('app', 'Full Name')],
        ];

        if ($fieldLayout = Craft::$app->getFields()->getLayoutByType(User::class)) {
            $fields = $this->getStringCustomFieldOptions($fieldLayout->getCustomFields());

            $options = array_merge($options, $fields);
        }

        return $options;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'defaultUser' => [
                'name' => 'defaultUser',
                'type' => UserInterface::getType(),
                'args' => UserArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getDefaultValueQuery() ? $class->getDefaultValueQuery()->one() : null;
                },
            ],
            'users' => [
                'name' => 'users',
                'type' => Type::listOf(UserInterface::getType()),
                'args' => UserArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getElementsQuery()->all();
                },
            ],
        ]);
    }

    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(UserInterface::getType())),
            'args' => UserArguments::getArguments(),
            'resolve' => UserResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        $options = $this->getSourceOptions();

        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The option shown initially, when no option is selected.'),
                'name' => 'placeholder',
                'validation' => 'required',
                'required' => true,
                'if' => 'displayType == "dropdown"',
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'instructions' => Craft::t('formie', 'Which sources do you want to select users from?'),
                'name' => 'sources',
                'options' => $options,
                'showAllOption' => true,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Select a default user to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => static::elementType(),
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit'),
                'instructions' => Craft::t('formie', 'Limit the number of selectable users.'),
                'name' => 'limit',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'instructions' => Craft::t('formie', 'Limit the number of available users.'),
                'name' => 'limitOptions',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'instructions' => Craft::t('formie', 'Select what to use as the label for each user.'),
                'name' => 'labelSource',
                'options' => $this->getLabelSourceOptions(),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Options Order'),
                'instructions' => Craft::t('formie', 'Select what order to show users by.'),
                'name' => 'orderBy',
                'options' => $this->getOrderByOptions(),
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'instructions' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    ['label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown'],
                    ['label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes'],
                    ['label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio'],
                ],
            ]),
            ...$this->defineElementFieldMultiSelectAppearanceSchema(),
            ...$this->defineElementFieldSearchableDropdownAppearanceSchema(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}
