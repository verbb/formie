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
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\OptionData;
use verbb\formie\fields\data\SingleOptionFieldData;
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


    // Properties
    // =========================================================================

    public bool $allowMultipleSources = false;

    private ?TagGroup $_tagGroup = null;


    // Public Methods
    // =========================================================================

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['placeholder'] = Craft::t('formie', 'Select a tag');

        return $settings;
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

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/tags/preview', [
            'field' => $this,
        ]);
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
            'value' => 'fui-input',
        ];

        // Special-case for Tag fields - not really a dropdown
        if ($this->displayType === 'dropdown') {
            unset($config['hasMultiNamespace']);
            unset($config['options']);
            unset($config['multi']);

            return new Hidden($config);
        }

        return parent::getDisplayTypeField();
    }

    public function getDisplayTypeValue($value): MultiOptionsFieldData|SingleOptionFieldData|null
    {
        if ($this->displayType === 'dropdown') {
            $options = [];

            foreach ($value->all() as $element) {
                $options[] = new OptionData($element->title, $element->id, true);
            }

            return new MultiOptionsFieldData($options);
        }

        return parent::getDisplayTypeValue($value);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/tags.js'),
            'module' => 'FormieTags',
        ];
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
                'help' => Craft::t('formie', 'Which source do you want to select tags from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags')]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select default tags to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => Craft::t('formie', 'Choose'),
                'config' => [
                    'jsClass' => $this->cpInputJsClass,
                    'elementType' => FormieTag::class,
                ],
            ]),
        ];
    }

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
            SchemaHelper::includeInEmailField(),
            SchemaHelper::emailNotificationValue(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable variants.'),
                'name' => 'limit',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Limit Options'),
                'help' => Craft::t('formie', 'Limit the number of available variants.'),
                'name' => 'limitOptions',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'help' => Craft::t('formie', 'Select what to use as the label for each entry.'),
                'name' => 'labelSource',
                'options' => $labelSourceOptions,
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
