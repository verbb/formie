<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\Tag as FormieTag;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Tag;
use craft\elements\db\ElementQueryInterface;
use craft\fields\Tags as CraftTags;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\gql\arguments\elements\Tag as TagArguments;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\TagGroup;

use GraphQL\Type\Definition\Type;

use Throwable;

class Tags extends CraftTags implements FormFieldInterface
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
        getDisplayTypeValue as traitGetDisplayTypeValue;
        getDisplayTypeField as traitGetDisplayTypeField;
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
        return Craft::t('formie', 'Tags');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/tags/icon.svg';
    }


    // Properties
    // =========================================================================

    public bool $searchable = true;

    protected string $inputTemplate = 'formie/_includes/element-select-input';

    private ?TagGroup $_tagGroup = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
        // Config normalization
        self::normalizeConfig($config);

        parent::__construct($config);

        $this->labelSource = 'title';
    }

    public function getSavedFieldConfig(): array
    {
        $settings = $this->traitGetSavedFieldConfig();

        return $this->modifyFieldSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $tagGroup = $this->_getTagGroup();

        if (!is_string($value) || !$tagGroup) {
            return parent::normalizeValue($value, $element);
        }

        $value = Json::decodeIfJson($value);
        $siteId = $this->targetSiteId($element);
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
            ->siteId($siteId)
            ->id(array_filter($tagsIds))
            ->fixedOrder();
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        $options = $this->getSourceOptions();

        return [
            'sourceOptions' => $options,
            'warning' => count($options) === 1 ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags')]) : false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        $tag = null;
        $tags = Craft::$app->getTags()->getAllTagGroups();

        if (!empty($tags)) {
            $tag = 'taggroup:' . $tags[0]->uid;
        }

        return [
            'source' => $tag,
            'placeholder' => Craft::t('formie', 'Select a tag'),
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/tags/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $renderOptions);
        $inputOptions['tags'] = $this->getTags();

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getDisplayTypeField(): FormFieldInterface
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
            return new Hidden($config);
        }

        return $this->traitGetDisplayTypeField();
    }

    public function getDisplayTypeValue($value): MultiOptionsFieldData|SingleOptionFieldData|null
    {
        if ($this->displayType === 'dropdown') {
            $options = [];

            if ($value) {
                foreach ($value->all() as $element) {
                    $options[] = new OptionData($element->title, $element->id, true);
                }
            }

            return new MultiOptionsFieldData($options);
        }

        return $this->traitGetDisplayTypeValue();
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

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/tags.js', true),
            'module' => 'FormieTags',
        ];
    }

    /**
     * @return Tag[]
     */
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

    /**
     * @inheritDoc
     */
    public function getSourceOptions(): array
    {
        $options = parent::getSourceOptions();

        return array_merge([['label' => Craft::t('formie', 'Select an option'), 'value' => '']], $options);
    }

    /**
     * Returns the list of selectable tags.
     *
     * @return ElementQueryInterface
     */
    public function getElementsQuery(): ElementQueryInterface
    {
        $query = Tag::find();

        if ($group = $this->_getTagGroup()) {
            $query->group($group);
        }

        // Check if a default value has been set AND we're limiting. We need to resolve the value before limiting
        if ($this->defaultValue && $this->limitOptions) {
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

        $query->limit($this->limitOptions);
        $query->orderBy('title ASC');

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
        return array_merge($this->traitGetSettingGqlTypes(), [
           'defaultValue' => [
                'name' => 'defaultValue',
                'type' => Type::string(),
                'resolve' => function($field) {
                    $value = $field->defaultValue;

                    return is_array($value) ? Json::encode($value) : $value;
                },
            ],
            'defaultTag' => [
                'name' => 'defaultTag',
                'type' => TagInterface::getType(),
                'resolve' => TagResolver::class.'::resolve',
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
                'selectionLabel' => self::defaultSelectionLabel(),
                'config' => [
                    'jsClass' => $this->inputJsClass,
                    'elementType' => FormieTag::class,
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

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns the tag group associated with this field.
     *
     * @return TagGroup|null
     */
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

    /**
     * Returns the tag group ID this field is associated with.
     *
     * @return string|int|null
     */
    private function _getTagGroupId(): string|int|null
    {
        if (!preg_match('/^taggroup:(([0-9a-f\-]+))$/', $this->source, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
