<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\formfields\Password;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\db\Table;
use craft\elements\User as UserElement;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class User extends Element
{
    // Properties
    // =========================================================================

    public $groupIds = [];
    public $activateUser = false;
    public $mergeUserGroups = false;
    public $sendActivationEmail = true;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'User');
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create User elements.');
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $fields = $this->getFormSettingValue('elements')[0]->fields ?? [];

        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function fetchFormSettings()
    {
        $customFields = [];
        $fields = [];

        $userFieldLayout = Craft::$app->getFields()->getLayoutByType(UserElement::class);

        foreach ($userFieldLayout->getFields() as $field) {
            $fields[] = new IntegrationField([
                'handle' => $field->handle,
                'name' => $field->name,
                'type' => $this->getFieldTypeForField(get_class($field)),
                'required' => (bool)$field->required,
            ]);
        }

        $customFields[] = new IntegrationCollection([
            'id' => 'user',
            'name' => 'User',
            'fields' => $fields,
        ]);

        return new IntegrationFormSettings([
            'elements' => $customFields,
            'attributes' => $this->getElementAttributes(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getElementAttributes()
    {
        return [
            new IntegrationField([
                'name' => Craft::t('app', 'Username'),
                'handle' => 'username',
                'required' => true,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'First Name'),
                'handle' => 'firstName',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Last Name'),
                'handle' => 'lastName',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Email'),
                'handle' => 'email',
                'required' => true,
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Password'),
                'handle' => 'newPassword',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Photo'),
                'handle' => 'photo',
                'type' => IntegrationField::TYPE_NUMBER,
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUpdateAttributes()
    {
        $attributes = [
            new IntegrationField([
                'name' => Craft::t('app', 'ID'),
                'handle' => 'id',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Username'),
                'handle' => 'username',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'First Name'),
                'handle' => 'firstName',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Last Name'),
                'handle' => 'lastName',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Email'),
                'handle' => 'email',
            ]),
        ];

        $userFieldLayout = Craft::$app->getFields()->getLayoutByType(UserElement::class);

        foreach ($userFieldLayout->getFields() as $field) {
            if (!$this->fieldCanBeUniqueId($field)) {
                continue;
            }

            $attributes[] = new IntegrationField([
                'handle' => $field->handle,
                'name' => $field->name,
                'type' => $this->getFieldTypeForField(get_class($field)),
            ]);
        }


        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission)
    {
        try {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $user = $this->getElementForPayload(UserElement::class, $submission);

            // If a new user, set as pending
            if (!$user->id) {
                $user->pending = true;
            }

            // Get the source form field if we're mapping the password. A few things to do.
            $passwordField = $this->_getPasswordField($submission);
            $hashedPassword = null;

            $userGroups = [];

            if ($this->mergeUserGroups) {
                $userGroups = $user->getGroups();
            }

            foreach ($this->groupIds as $groupId) {
                if ($group = Craft::$app->getUserGroups()->getGroupById($groupId)) {
                    $userGroups[] = $group;
                }
            }

            if ($userGroups) {
                $user->setGroups($userGroups);
            }

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());
            
            // Filter null values
            $attributeValues = array_filter($attributeValues, function($var) {
                return $var !== null;
            });

            // Check if the password was mapped, as if the source field was a Password field.
            // The value will already be hashed and we need to do a manual DB-level update
            if (isset($attributeValues['newPassword']) && $passwordField) {
                // If this a Password field?
                if ($passwordField instanceof Password) {
                    $hashedPassword = ArrayHelper::remove($attributeValues, 'newPassword');
                }
            }

            // Set the attributes on the user element
            $this->_setElementAttributes($user, $attributeValues);

            $fields = $this->getFormSettingValue('elements')[0]->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);
            
            // Filter null values
            $fieldValues = array_filter($fieldValues, function($var) {
                return $var !== null;
            });

            $user->setFieldValues($fieldValues);

            // Although empty, because we pass via reference, we need variables
            $endpoint = '';
            $method = '';

            // Allow events to cancel sending - return as success
            if (!$this->beforeSendPayload($submission, $endpoint, $user, $method)) {
                return true;
            }

            if (!$user->validate()) {
                Integration::error($this, Craft::t('formie', 'Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($user->getErrors()),
                ]), true);

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($user)) {
                Integration::error($this, Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($user->getErrors()),
                ]), true);
                
                return false;
            }

            // Has a Password field been used to map the value? Do a direct DB update as it's been hashed already.
            // This also needs to be done before sending activation emails
            if ($hashedPassword) {
                Db::update(Table::USERS, ['password' => $hashedPassword], ['id' => $user->id], [], false);

                // Update the user model with the password, as activation emails require this
                $user->password = $hashedPassword;
            }

            $autoLogin = false;

            if ($user->getStatus() == UserElement::STATUS_PENDING) {
                if ($this->activateUser) {
                    Craft::$app->getUsers()->activateUser($user);

                    $autoLogin = true;
                }

                if ($this->sendActivationEmail) {
                    Craft::$app->getUsers()->sendActivationEmail($user);
                }
            }

            if ($userGroups) {
                $groupIds = ArrayHelper::getColumn($userGroups, 'id');

                Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
            }

            // Important to wipe out the field mapped to their password, and save the submission. We don't want to permanently
            // store the password content against the submission.
            if ($passwordField) {
                $submission->setFieldValue($passwordField->handle, '');
            
                if (!Craft::$app->getElements()->saveElement($submission, false)) {
                    Integration::error($this, Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                        'type' => $this->handle,
                        'error' => Json::encode($submission->getErrors()),
                    ]), true);
                    
                    return false;
                }
            }

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, '', $user, '', [])) {
                return true;
            }

            // Maybe login the user after activation
            if ($autoLogin && $generalConfig->autoLoginAfterAccountActivation) {
                // When run from the queue, this will fail due to session being unavailable
                if (!$this->getQueueJob()) {
                    Craft::$app->getUser()->login($user, $generalConfig->userSessionDuration);
                }
            }
        } catch (\Throwable $e) {
            $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'submission' => $submission->id,
            ]);

            Formie::error($error);

            return new IntegrationResponse(false, $error);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getGroupOptions()
    {
        $userGroups = [];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $key => $group) {
            $userGroups[] = [
                'label' => $group->name,
                'value' => $group->id,
            ];
        }

        return $userGroups;
    }
    

    // Private Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    private function _setElementAttributes($user, $attributes)
    {
        foreach ($attributes as $userFieldHandle => $fieldValue) {
            // Special handling for photo - must be an asset. Actually provided as an Asset ID.
            if ($userFieldHandle === 'photo') {
                // Fetch the asset, if it exists
                $asset = Craft::$app->getAssets()->getAssetById($fieldValue);

                $fieldValue = $asset ?? null;
            }

            $user->{$userFieldHandle} = $fieldValue;
        }
    }

    private function _getPasswordField($submission)
    {
        $passwordFieldHandle = $this->attributeMapping['newPassword'] ?? '';

        if ($passwordFieldHandle) {
            $passwordFieldHandle = str_replace(['{', '}'], ['', ''], $passwordFieldHandle);

            // Find the form field
            if ($form = $submission->getForm()) {
                if ($field = $form->getFieldByHandle($passwordFieldHandle)) {
                    return $field;
                }
            }
        }

        return null;
    }
}