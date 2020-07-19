<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\elements\Category;
use craft\fields\Categories as CraftCategories;
use craft\helpers\UrlHelper;

use craft\models\CategoryGroup;

class Categories extends CraftCategories implements FormFieldInterface
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
     * @var CategoryGroup
     */
    private $_categoryGroup;


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


    // Public Methods
    // =========================================================================

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
    public function getIsFieldset(): bool
    {
        return $this->getIsMultiLevel();
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
        $inputOptions['categoriesQuery'] = $this->getCategoriesQuery();
        $inputOptions['isMultiLevel'] = $this->getIsMultiLevel();
        $inputOptions['allowMultiple'] = $this->branchLimit > 1;

        return $inputOptions;
    }

    /**
     * Returns the list of selectable categories.
     *
     * @return Category[]
     */
    public function getCategoriesQuery()
    {
        $group = $this->_getCategoryGroup();

        return Category::find()->group($group)->orderBy('title ASC');
    }

    /**
     * Returns true if the categories have more than 1 level.
     *
     * @return bool
     */
    public function getIsMultiLevel(): bool
    {
        $group = $this->_getCategoryGroup();

        return Category::find()->group($group)->hasDescendants()->exists();
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
                'help' => Craft::t('formie', 'Which source do you want to select categories from?'),
                'name' => 'source',
                'options' => $options,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) === 1 ? 'hidden' : false,
                'warning' => count($options) === 1 ? Craft::t('formie', 'No category groups available. View [category settings]({link}).', ['link' => UrlHelper::cpUrl('settings/categories') ]) : false,
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
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Branch Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable category branches.'),
                'name' => 'branchLimit',
                'size' => '3',
                'class' => 'text',
                'validation' => 'optional|number|min:0',
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
        ];
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns the category group.
     *
     * @return CategoryGroup|null
     */
    private function _getCategoryGroup()
    {
        if ($this->source === '*') {
            return null;
        }

        if (!$this->_categoryGroup && is_array($this->source)) {
            list(, $uid) = explode(':', $this->source);
            return Craft::$app->getCategories()->getGroupByUid($uid);
        }

        return $this->_categoryGroup;
    }
}
