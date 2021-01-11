<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\elements\User as UserElement;
// use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

class User extends Element
{
    // Properties
    // =========================================================================

    public $groupIds = [];
    public $activateUser = false;
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
        ];
    }

    /**
     * @inheritDoc
     */
    public function sendPayload(Submission $submission)
    {
        try {
            $user = new UserElement();

            $userGroups = [];

            foreach ($this->groupIds as $groupId) {
                if ($group = Craft::$app->getUserGroups()->getGroupById($groupId)) {
                    $userGroups[] = $group;
                }
            }

            if ($userGroups) {
                $user->setGroups($userGroups);
            }

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());
            
            foreach ($attributeValues as $userFieldHandle => $fieldValue) {
                $user->{$userFieldHandle} = $fieldValue;
            }

            $fields = $this->getFormSettingValue('elements')[0]->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            $user->setFieldValues($fieldValues);

            if (!$user->validate()) {
                Formie::error(Craft::t('formie', 'Unable to validate “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($user->getErrors()),
                ]));

                return false;
            }

            if (!Craft::$app->getElements()->saveElement($user)) {
                Formie::error(Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                    'type' => $this->handle,
                    'error' => Json::encode($user->getErrors()),
                ]));
                
                return false;
            }

            if ($user->getStatus() == UserElement::STATUS_PENDING) {
                if ($this->activateUser) {
                    Craft::$app->getUsers()->activateUser($user);
                }

                if ($this->sendActivationEmail) {
                    Craft::$app->getUsers()->sendActivationEmail($user);
                }
            }

            if ($userGroups) {
                Craft::$app->getUsers()->assignUserToGroups($user->id, $this->groupIds);
            }

            // Important to wipe out the field mapped to their password, and save the submission. We don't want to permanently
            // store the password content against the submission.
            $passwordFieldHandle = $this->attributeMapping['newPassword'] ?? '';

            if ($passwordFieldHandle) {
                $passwordFieldHandle = str_replace(['{', '}'], ['', ''], $passwordFieldHandle);

                $submission->setFieldValue($passwordFieldHandle, '');
            
                if (!Craft::$app->getElements()->saveElement($submission, false)) {
                    Formie::error(Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                        'type' => $this->handle,
                        'error' => Json::encode($submission->getErrors()),
                    ]));
                    
                    return false;
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

            return false;
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
}