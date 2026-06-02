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


    // Public Methods
    // =========================================================================

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
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
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'instructions' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'instructions' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => 'required',
            ]),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
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
                ->core([
                    'type' => 'password',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'autocomplete' => 'off',
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-password-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'password',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'data-formie-required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                    'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
                ])
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

    protected function defineValueClass(): ?string
    {
        return StringFieldValue::class;
    }
}
