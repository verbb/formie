<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Tag;
use craft\fields\Tags as CraftTags;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\TagGroup;

use Throwable;

class Tags extends CraftTags implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        getFrontEndInputOptions as traitGetFrontendInputOptions;
    }
    use RelationFieldTrait;


    // Properties
    // =========================================================================

    protected $inputTemplate = 'formie/_includes/elementSelect';


    // Private Properties
    // =========================================================================

    /**
     * @var
     */
    private $_tagGroupId;


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


    // Public Methods
    // =========================================================================

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
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
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
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
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
