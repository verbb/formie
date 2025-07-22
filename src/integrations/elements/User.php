<?php
namespace verbb\formie\integrations\elements;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Password;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResponse;

use Craft;
use craft\elements\Address as AddressElement;
use craft\elements\User as UserElement;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Db;
use craft\helpers\Json;

use Throwable;

class User extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'User');
    }


    // Properties
    // =========================================================================

    public array $groupIds = [];
    public array $groupUids = [];
    public bool $activateUser = false;
    public bool $mergeUserGroups = false;
    public bool $sendActivationEmail = true;
    public ?AddressElement $address = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Map content provided by form submissions to create {name} elements.', ['name' => static::displayName()]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $customFields = [];

        $userFieldLayout = Craft::$app->getFields()->getLayoutByType(UserElement::class);
        $fields = $this->getFieldLayoutFields($userFieldLayout);

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

    public function getElementAttributes(): array
    {
        $attributes = [
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
                'name' => Craft::t('app', 'Full Name'),
                'handle' => 'fullName',
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

        // Only include address fields if that native field has been added
        if ($fieldLayout = Craft::$app->getFields()->getLayoutByType(UserElement::class)) {
            if ($fieldLayout->isFieldIncluded('addresses')) {
                if ($addressField = $fieldLayout->getField('addresses')) {
                    $fields = [];

                    foreach (Craft::$app->getFields()->getLayoutByType(AddressElement::class)->getTabs() as $tab) {
                        foreach ($tab->getElements() as $layoutElement) {
                            if ($layoutElement instanceof BaseNativeField) {
                                if (!$layoutElement->label()) {
                                    continue;
                                }

                                $attributes[] = new IntegrationField([
                                    'handle' => $addressField->attribute . '__' . $layoutElement->attribute,
                                    'name' => Craft::t('formie', '{addressLabel}: {label}', [
                                        'addressLabel' => $addressField->label(), 
                                        'label' => $layoutElement->label(),
                                    ]),
                                    'type' => $this->getFieldTypeForField(get_class($layoutElement)),
                                    'sourceType' => get_class($layoutElement),
                                    'required' => $layoutElement->required,
                                ]);
                            } else if ($layoutElement instanceof CustomField) {
                                $field = $layoutElement->getField();

                                $attributes[] = new IntegrationField([
                                    'handle' => $addressField->attribute . '__' . $field->handle,
                                    'name' => Craft::t('formie', '{addressLabel}: {label}', [
                                        'addressLabel' => $addressField->label(),
                                        'label' => $field->name,
                                    ]),
                                    'type' => $this->getFieldTypeForField(get_class($field)),
                                    'sourceType' => get_class($field),
                                    'required' => $layoutElement->required,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return $attributes;
    }

    public function getUpdateAttributes(): array
    {
        $attributes = [];

        $attributes['users'] = [
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
                'name' => Craft::t('app', 'Full Name'),
                'handle' => 'fullName',
            ]),
            new IntegrationField([
                'name' => Craft::t('app', 'Email'),
                'handle' => 'email',
            ]),
        ];

        $userFieldLayout = Craft::$app->getFields()->getLayoutByType(UserElement::class);

        foreach ($userFieldLayout->getCustomFields() as $field) {
            if (!$this->fieldCanBeUniqueId($field)) {
                continue;
            }

            $attributes['users'][] = new IntegrationField([
                'handle' => $field->handle,
                'name' => $field->name,
                'type' => $this->getFieldTypeForField(get_class($field)),
                'sourceType' => get_class($field),
            ]);
        }


        return $attributes;
    }

    public function sendPayload(Submission $submission): IntegrationResponse|bool
    {
        try {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $user = $this->getElementForPayload(UserElement::class, 'users', $submission);

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

            foreach ($this->groupUids as $groupUid) {
                if ($group = Craft::$app->getUserGroups()->getGroupByUid($groupUid)) {
                    $userGroups[] = $group;
                }
            }

            if ($userGroups) {
                $user->setGroups($userGroups);
            }

            $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());

            // Filter null values
            if (!$this->overwriteValues) {
                $attributeValues = ArrayHelper::filterNull($attributeValues);
            }

            // Check if the password was mapped, as if the source field was a Password field.
            // The value will already be hashed, and we need to do a manual DB-level update
            if (isset($attributeValues['newPassword'])) {
                // If this a Password field?
                if ($passwordField instanceof Password) {
                    $hashedPassword = ArrayHelper::remove($attributeValues, 'newPassword');

                    // If this is **not** being run via the queue, the password won't be serialized and hashed yet, so ensure it's hashed
                    if (Craft::$app->getRequest()->getIsSiteRequest()) {
                        $hashedPassword = Craft::$app->getSecurity()->hashPassword($hashedPassword);
                    }
                }
            }

            // Set the attributes on the user element
            $this->_setElementAttributes($user, $attributeValues);

            $fields = $this->getFormSettingValue('elements')[0]->fields ?? [];
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

            // Filter null values
            if (!$this->overwriteValues) {
                $fieldValues = ArrayHelper::filterNull($fieldValues);
            }

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

            if (!Craft::$app->getElements()->saveElement($user, true, true, $this->updateSearchIndexes)) {
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

                    if ($user->getErrors()) {
                        Integration::error($this, Craft::t('formie', 'Unable to activate user for “{type}” element integration. Error: {error}.', [
                            'type' => $this->handle,
                            'error' => Json::encode($user->getErrors()),
                        ]), true);

                        return false;
                    }

                    $autoLogin = true;
                }

                if ($this->sendActivationEmail) {
                    if (!Craft::$app->getUsers()->sendActivationEmail($user)) {
                        Integration::error($this, Craft::t('formie', 'Unable to send user activation email for “{type}” element integration. Error: {error}.', [
                            'type' => $this->handle,
                            'error' => Json::encode($user->getErrors()),
                        ]), true);

                        return false;
                    }
                }
            }

            if ($userGroups) {
                $groupIds = ArrayHelper::getColumn($userGroups, 'id');

                if (!Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds)) {
                    Integration::error($this, Craft::t('formie', 'Unable to assign user groups for “{type}” element integration. Error: {error}.', [
                            'type' => $this->handle,
                            'error' => Json::encode($user->getErrors()),
                        ]), true);

                        return false;
                }
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

            // Where there any addresses to update?
            if ($this->address) {
                $this->address->setOwner($user);

                if (!Craft::$app->getElements()->saveElement($this->address)) {
                    Integration::error($this, Craft::t('formie', 'Unable to save “{type}” element integration. Error: {error}.', [
                        'type' => $this->handle,
                        'error' => Json::encode($this->address->getErrors()),
                    ]), true);

                    return false;
                }
            }

            // Allow events to say the response is invalid
            if (!$this->afterSendPayload($submission, '', $user, '', [])) {
                return true;
            }

            // Maybe login the user after activation
            if ($autoLogin && $generalConfig->autoLoginAfterAccountActivation && Craft::$app->getUser()->getIsGuest()) {
                // When run from the queue, this will fail due to session being unavailable
                if (!$this->getQueueJob()) {
                    Craft::$app->getUser()->login($user, $generalConfig->userSessionDuration);
                }
            }
        } catch (Throwable $e) {
            $error = Craft::t('formie', 'Element integration failed for submission “{submission}”. Error: {error} {file}:{line}. Trace: “{trace}”.', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'submission' => $submission->id,
            ]);

            Formie::error($error);

            return new IntegrationResponse(false, [$error]);
        }

        return true;
    }

    public function getGroupOptions(): array
    {
        $userGroups = [];

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $key => $group) {
            $userGroups[] = [
                'label' => $group->name,
                'value' => $group->uid,
            ];
        }

        return $userGroups;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $fields = $this->getFormSettingValue('elements')[0]->fields ?? [];

        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _setElementAttributes($user, $attributes): void
    {
        foreach ($attributes as $userFieldHandle => $fieldValue) {
            // Special handling for photo - must be an asset. Actually provided as an Asset ID.
            if ($userFieldHandle === 'photo') {
                if (is_array($fieldValue)) {
                    $fieldValue = $fieldValue[0] ?? null;
                }

                // If explicitly null, that's okay, we might be overwriting values
                if ($fieldValue !== null) {
                    // Fetch the asset, if it exists
                    $asset = Craft::$app->getAssets()->getAssetById($fieldValue);

                    $fieldValue = $asset ?? null;
                }
            }

            // Special handling for address fields - note we need to set multiple attributes and save for later
            // after we've processed the user.
            if (str_starts_with($userFieldHandle, 'addresses')) {
                $handles = explode('__', $userFieldHandle);

                // Use the address we're currently dealing with, or find an existing one based off title (label)
                if (!$this->address) {
                    $addressTitle = $attributes['addresses__title'] ?? null;

                    $this->address = AddressElement::find()
                        ->title($addressTitle)
                        ->owner($user)
                        ->one() ?? new AddressElement();
                }

                $this->address->{$handles[1]} = $fieldValue;

                // Don't set these values on the user element
                continue;
            }

            $user->{$userFieldHandle} = $fieldValue;
        }
    }

    private function _getPasswordField($submission)
    {
        $passwordFieldHandle = $this->attributeMapping['newPassword'] ?? '';

        if ($passwordFieldHandle) {
            $passwordFieldHandle = str_replace(['{field:', '}'], ['', ''], $passwordFieldHandle);

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
