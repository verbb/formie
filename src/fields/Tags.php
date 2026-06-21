<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ElementField;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\Tag as FormieTag;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\OptionValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Tag;
use craft\elements\db\ElementQueryInterface;
use craft\fields\Tags as CraftTags;
use craft\gql\arguments\elements\Tag as TagArguments;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\resolvers\elements\Tag as TagResolver;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\TagGroup;
use craft\services\Gql as GqlService;

use GraphQL\Type\Definition\Type;

use Throwable;

class Tags extends ElementField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Tags');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/tags/icon.svg';
    }

    public static function elementType(): string
    {
        return Tag::class;
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentTypeFromConfig(array $config): Type|array
    {
        return static::gqlElementContentTypeDefinitionFromConfig(
            $config,
            TagInterface::getType(),
            TagArguments::getArguments(),
            TagResolver::class,
        );
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return static::gqlElementContentMutationArgumentTypeDefinitionFromConfig($config);
    }

    protected static function defineOptionSource(): ?array
    {
        return [
            'handle' => 'tags',
            'label' => static::displayName(),
        ];
    }


    // Properties
    // =========================================================================

    public bool $allowMultipleSources = false;

    private ?TagGroup $_tagGroup = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['placeholder'] = $config['placeholder'] ?? Craft::t('formie', 'Select a tag');

        parent::__construct($config);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $tagGroup = $this->_getTagGroup();

        if (!is_string($value) || !$tagGroup) {
            return parent::normalizeValue($value, $element);
        }

        $value = Json::decodeIfJson($value);
        $elementsService = Craft::$app->getElements();

        if (!is_array($value)) {
            $value = StringHelper::explode($value, ' ');

            $value = array_map(function($t) {
                if ($t) {
                    return [
                        'value' => $t,
                    ];
                }
            }, $value);
        }

        $value = array_filter($value);

        $tagsIds = [];
        foreach ($value as $tagJson) {
            if (!isset($tagJson['id'])) {
                $tag = Tag::find()
                    ->group($tagGroup)
                    ->title($tagJson['value'])
                    ->one();

                if (!$tag) {
                    $tag = new Tag();
                    $tag->title = $tagJson['value'];
                    $tag->groupId = $tagGroup->id;

                    try {
                        $elementsService->saveElement($tag, false);
                    } catch (Throwable $e) {
                        Formie::error('Failed to save tag: ' . $e->getMessage());

                        continue;
                    }
                }

                $tagsIds[] = $tag->id;
            } else {
                $tagsIds[] = $tagJson['id'];
            }
        }

        return Tag::find()
            ->id(array_filter($tagsIds))
            ->fixedOrder();
    }

    public function getFieldTypeConfigData(): array
    {
        $options = $this->getSourceOptions();

        return [
            'warning' => count($options) === 1 ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags')]) : false,
        ];
    }

    public function getOptionSourceWarning(array $sourceOptions): ?string
    {
        return $sourceOptions === []
            ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags')])
            : null;
    }

    public function getOptionSourceSourceOptions(): array
    {
        return array_values(array_filter(
            $this->getSourceOptions(),
            static fn(array $option): bool => (string)($option['value'] ?? '') !== '',
        ));
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewElementField(),
        ];
    }

    public function getDisplayTypeField(): FieldInterface
    {
        $config = $this->getDisplayTypeFieldConfig();

        $config['inputAttributes'][] = [
            'label' => 'data-formie-tags',
            'value' => Json::encode($this->getTags()),
        ];

        $config['inputAttributes'][] = [
            'label' => 'class',
            'value' => 'formie-input',
        ];

        // Special-case for Tag fields - not really a dropdown
        if ($this->displayType === 'dropdown') {
            return $this->createPresentationField(Hidden::class, $config);
        }

        return parent::getDisplayTypeField();
    }

    public function getDisplayTypeValue($value): MultiOptionFieldValue|SingleOptionFieldValue|null
    {
        if ($this->displayType === 'dropdown') {
            $options = [];

            foreach ($value->all() as $element) {
                $options[] = new OptionValue($element->title, $element->id, true);
            }

            return new MultiOptionFieldValue($options);
        }

        return parent::getDisplayTypeValue($value);
    }

    public function getTags(): array
    {
        if ($group = $this->_getTagGroup()) {
            $tags = [];

            foreach (Tag::find()->group($group)->orderBy('title ASC')->all() as $tag) {
                $tags[] = [
                    'value' => $tag->title,
                    'id' => $tag->id,
                ];
            }

            return $tags;
        }

        return [];
    }

    public function getSourceOptions(): array
    {
        $options = parent::getSourceOptions();

        return array_merge([['label' => Craft::t('formie', 'Select an option'), 'value' => '']], $options);
    }

    public function defineLabelSourceOptions(): array
    {
        $options = [
            ['value' => 'title', 'label' => Craft::t('app', 'Title')],
        ];

        $extraOptions = [];

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $groupId = $source['criteria']['groupId'] ?? null;

                if ($groupId && !is_array($groupId)) {
                    $group = Craft::$app->getTags()->getTagGroupById($groupId);

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
            'defaultTag' => [
                'name' => 'defaultTag',
                'type' => TagInterface::getType(),
                'args' => TagArguments::getArguments(),
                'resolve' => function($class) {
                    return $class->getDefaultValueQuery() ? $class->getDefaultValueQuery()->one() : null;
                },
            ],
            'tags' => [
                'name' => 'tags',
                'type' => Type::listOf(TagInterface::getType()),
                'args' => TagArguments::getArguments(),
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
            'type' => Type::nonNull(Type::listOf(TagInterface::getType())),
            'args' => TagArguments::getArguments(),
            'resolve' => TagResolver::class . '::resolve',
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
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Source'),
                'instructions' => Craft::t('formie', 'Which source do you want to select tags from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Select default tags to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => FormieTag::class,
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        $labelSourceOptions = $this->getLabelSourceOptions();

        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::emailFieldSummaryValue(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit'),
                'instructions' => Craft::t('formie', 'Limit the number of selectable variants.'),
                'name' => 'limit',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'instructions' => Craft::t('formie', 'Limit the number of available variants.'),
                'name' => 'limitOptions',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'instructions' => Craft::t('formie', 'Select what to use as the label for each entry.'),
                'name' => 'labelSource',
                'options' => $labelSourceOptions,
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


    // Private Methods
    // =========================================================================

    private function _getTagGroup(): ?TagGroup
    {
        if ($this->_tagGroup !== null) {
            return $this->_tagGroup;
        }

        $tagGroupId = $this->_getTagGroupId();

        if ($tagGroupId !== null) {
            return $this->_tagGroup = Craft::$app->getTags()->getTagGroupByUid($tagGroupId);
        }

        return null;
    }

    private function _getTagGroupId(): string|int|null
    {
        if (!preg_match('/^taggroup:(([0-9a-f\-]+))$/', $this->source, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
