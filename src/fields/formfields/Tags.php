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
use craft\fields\Tags as CraftTags;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\TagGroup;

use Throwable;

class Tags extends CraftTags implements FormFieldInterface
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
        getDisplayTypeValue as traitGetDisplayTypeValue;
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

    /**
     * @var bool
     */
    public $searchable = true;

    /**
     * @var string
     */
    protected $inputTemplate = 'formie/_includes/element-select-input';

    /**
     * @var TagGroup
     */
    private $_tagGroupId;


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
    public function normalizeValue($value, ElementInterface $element = null)
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
            $value = array_map(function ($t) {
                return [
                    'value' => $t,
                ];
            }, $value);
        }

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
            'warning' => count($options) === 1 ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags') ]) : false,
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/tags/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $options);
        $inputOptions['tags'] = $this->getTags();

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
     * @inheritdoc
     */
    public function getFrontEndJsModules()
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/tags.js', true),
            'module' => 'FormieTags',
        ];
    }

    /**
     * @return Tag[]
     */
    public function getTags()
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
     * @return Tag[]
     */
    public function getElementsQuery()
    {
        $query = Tag::find();

        if ($group = $this->_getTagGroup()) {
            $query->group($group);
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

        $query->orderBy('title ASC');

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
    public function defineLabelSourceOptions()
    {
        $options = [
            ['value' => 'title', 'label' => Craft::t('app', 'Title')],
        ];

        // Craft::dd($this->availableSources());

        foreach ($this->availableSources() as $source) {
            if (!isset($source['heading'])) {
                $groupId = $source['criteria']['groupId'] ?? null;

                if ($groupId && !is_array($groupId)) {
                    $group = Craft::$app->getTags()->getTagGroupById($groupId);

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
    public function getDisplayTypeValue($value)
    {
        // Special case for 'dropdown' in that its a tag-select/create field
        if ($this->displayType === 'dropdown') {
            return $value;
        }

        return $this->traitGetDisplayTypeValue($value);
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
                'help' => Craft::t('formie', 'Which source do you want to select tags from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No tag groups available. View [tag settings]({link}).', ['link' => UrlHelper::cpUrl('settings/tags') ]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select default tags to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => $this->defaultSelectionLabel(),
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
     * Returns the tag group associated with this field.
     *
     * @return TagGroup|null
     */
    private function _getTagGroup()
    {
        $tagGroupId = $this->_getTagGroupId();

        if ($tagGroupId !== false) {
            return Craft::$app->getTags()->getTagGroupByUid($tagGroupId);
        }

        return null;
    }

    /**
     * Returns the tag group ID this field is associated with.
     *
     * @return int|false
     */
    private function _getTagGroupId()
    {
        if ($this->_tagGroupId !== null) {
            return $this->_tagGroupId;
        }

        if (!preg_match('/^taggroup:(([0-9a-f\-]+))$/', $this->source, $matches)) {
            return $this->_tagGroupId = false;
        }

        return $this->_tagGroupId = $matches[1];
    }
}
