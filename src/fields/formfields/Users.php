<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\elements\User;
use craft\fields\Users as CraftUsers;
use craft\helpers\UrlHelper;

use craft\models\UserGroup;

class Users extends CraftUsers implements FormFieldInterface
{
    // Traits
    // =========================================================================

    use FormFieldTrait {
        getFrontendInputOptions as traitGetFrontendInputOptions;
    }
    use RelationFieldTrait;


    // Properties
    // =========================================================================

    protected $inputTemplate = 'formie/_includes/elementSelect';


    // Private Properties
    // =========================================================================

    /**
     * @var UserGroup
     */
    private $_userGroup;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Users');
    }

    /**
     * @inheritDoc
     */
    public static function getTemplatePath(): string
    {
        return 'fields/users';
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/users/icon.svg';
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
            'warning' => count($options) < 2 ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users') ]) : false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        $group = null;
        $groups = Craft::$app->getUserGroups()->getAllGroups();

        if (!empty($groups)) {
            $group = 'group:' . $groups[0]->uid;
        }

        return [
            'source' => $group,
            'placeholder' => Craft::t('formie', 'Select a user'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/users/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontendInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $options);
        $inputOptions['usersQuery'] = $this->getUsersQuery();

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml($value, $showName = true)
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/users/email', [
            'field' => $this,
            'value' => $value,
            'showName' => $showName,
        ]);
    }

    /**
     * Returns the list of selectable users.
     *
     * @return User[]
     */
    public function getUsersQuery()
    {
        $group = $this->_getUserGroup();

        return User::find()->group($group);
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
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select users from?'),
                'name' => 'sources',
                'options' => $options,
                'showAllOption' => true,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users') ]) : false,
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
                'label' => Craft::t('formie', 'Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable users.'),
                'name' => 'limit',
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
            SchemaHelper::inputAttributesField(),
        ];
    }


    // Private Methods
    // =========================================================================

    /**
     * Returns the user group.
     *
     * @return UserGroup|null
     */
    private function _getUserGroup()
    {
        if ($this->source === '*') {
            return null;
        }

        if (!$this->_userGroup && is_array($this->source)) {
            list(, $uid) = explode(':', $this->source);
            return Craft::$app->getUsers()->getGroupByUid($uid);
        }

        return $this->_userGroup;
    }
}
