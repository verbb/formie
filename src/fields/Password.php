<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\values\StringFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;

class Password extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Password');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/password/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?int $passwordMinLength = null;
    public bool $passwordRequireUppercase = false;
    public bool $passwordRequireLowercase = false;
    public bool $passwordRequireSpecialCharacter = false;


    // Public Methods
    // =========================================================================

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
    }

    protected function shouldTrimNormalizedPlainText(): bool
    {
        return false;
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        // Evaluate password fields differently. Because we don't populate the value back to the
        // field on reload, for multi-page forms this messes validation up. Because while for _this_
        // request we don't have a value, the submission stored does.
        // So, if the field is considered empty, do a fresh lookup to see if there's already a value.
        // We don't want to tell _what_ the value is, just if it can skip validation.
        $isValueEmpty = parent::isValueEmpty($value, $element);

        if ($isValueEmpty && $element->id) {
            $savedElement = Craft::$app->getElements()->getElementById($element->id, Submission::class);

            if ($savedElement) {
                $isValueEmpty = parent::isValueEmpty($savedElement->getFieldValue($this->valueKey()), $savedElement);
            }
        }

        return $isValueEmpty;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->passwordMinLength) {
            $rules[] = [$this->handle, 'validatePasswordMinLength'];
        }

        if ($this->passwordRequireUppercase) {
            $rules[] = [$this->handle, 'validatePasswordUppercase'];
        }

        if ($this->passwordRequireLowercase) {
            $rules[] = [$this->handle, 'validatePasswordLowercase'];
        }

        if ($this->passwordRequireSpecialCharacter) {
            $rules[] = [$this->handle, 'validatePasswordSpecialCharacter'];
        }

        return $rules;
    }

    public function validatePasswordMinLength(ElementInterface $element): void
    {
        $min = $this->passwordMinLength ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());

        if ($this->isValueEmpty($value, $element)) {
            return;
        }

        if (StringHelper::getCharacterCount($value) < $min) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_MIN_CHARACTERS, [
                'limit' => $min,
                'min' => $min,
            ]));
        }
    }

    public function validatePasswordUppercase(ElementInterface $element): void
    {
        if (!$this->passwordRequireUppercase) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());

        if ($this->isValueEmpty($value, $element)) {
            return;
        }

        if (!preg_match('/[A-Z]/u', $value)) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_PASSWORD_UPPERCASE));
        }
    }

    public function validatePasswordLowercase(ElementInterface $element): void
    {
        if (!$this->passwordRequireLowercase) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());

        if ($this->isValueEmpty($value, $element)) {
            return;
        }

        if (!preg_match('/[a-z]/u', $value)) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_PASSWORD_LOWERCASE));
        }
    }

    public function validatePasswordSpecialCharacter(ElementInterface $element): void
    {
        if (!$this->passwordRequireSpecialCharacter) {
            return;
        }

        $value = (string)$element->getFieldValue($this->valueKey());

        if ($this->isValueEmpty($value, $element)) {
            return;
        }

        if (!preg_match('/[^a-zA-Z0-9]/u', $value)) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_PASSWORD_SPECIAL_CHARACTER));
        }
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Only save the password as a hash
        if ($value) {
            $value = Craft::$app->getSecurity()->hashPassword($value);
        } else {
            // Important to reset to null, to prevent hash discovery from an empty string
            $value = null;
        }

        return parent::serializeValue($value, $element);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput([
                'type' => 'password',
            ]),
        ];
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Minimum Length'),
                'instructions' => Craft::t('formie', 'Set the minimum number of characters users must enter.'),
                'name' => 'passwordMinLength',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Minimum Characters Error Message'),
                'instructions' => ValidationMessagesHelper::tokenInstructions(['label', 'limit', 'min']),
                'name' => 'validationMessages.minCharacters',
                'if' => 'passwordMinLength',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Require Uppercase'),
                'instructions' => Craft::t('formie', 'Whether the password must contain at least one uppercase letter.'),
                'name' => 'passwordRequireUppercase',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Password Uppercase Error Message'),
                'instructions' => ValidationMessagesHelper::tokenInstructions(['label']),
                'name' => 'validationMessages.passwordUppercase',
                'if' => 'passwordRequireUppercase',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Require Lowercase'),
                'instructions' => Craft::t('formie', 'Whether the password must contain at least one lowercase letter.'),
                'name' => 'passwordRequireLowercase',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Password Lowercase Error Message'),
                'instructions' => ValidationMessagesHelper::tokenInstructions(['label']),
                'name' => 'validationMessages.passwordLowercase',
                'if' => 'passwordRequireLowercase',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Require Special Character'),
                'instructions' => Craft::t('formie', 'Whether the password must contain at least one special character.'),
                'name' => 'passwordRequireSpecialCharacter',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Password Special Character Error Message'),
                'instructions' => ValidationMessagesHelper::tokenInstructions(['label']),
                'name' => 'validationMessages.passwordSpecialCharacter',
                'if' => 'passwordRequireSpecialCharacter',
            ]),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
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
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['passwordMinLength'], 'number', 'integerOnly' => true, 'min' => 1],
        ]);
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'inputType' => 'password',
        ]);
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'password',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => $this->placeholder ?: null,
                    'autocomplete' => 'off',
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-password-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'password',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::passwordValidationClientAttributes(
                    $this,
                    $this->passwordMinLength,
                    (bool)$this->passwordRequireUppercase,
                    (bool)$this->passwordRequireLowercase,
                    (bool)$this->passwordRequireSpecialCharacter,
                )))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-password-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        // Mask the value for submissions (but no indication of length)
        if ($value) {
            return '•••••••••••••••••••••';
        }

        return '';
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        // Mask the value for submissions (but no indication of length)
        if ($value) {
            return '•••••••••••••••••••••';
        }

        return '';
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        // Hide the hashed password from exports as well
        return $this->getValueForSummary($value, $element);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Don't mess around with passwords for conditions. We don't really "know" the value
        // but more important will cause an infinite loop (somehow)
        return '•••••••••••••••••••••';
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if ($this->hasPasswordValidationRules()) {
            $modules[] = function(ClientModuleContext $context) {
                return new ClientModule([
                    'id' => 'password-validation',
                ]);
            };
        }

        return $modules;
    }

    protected function hasPasswordValidationRules(): bool
    {
        return (bool)$this->passwordMinLength
            || $this->passwordRequireUppercase
            || $this->passwordRequireLowercase
            || $this->passwordRequireSpecialCharacter;
    }

    protected function defineValueClass(): ?string
    {
        return StringFieldValue::class;
    }
}
