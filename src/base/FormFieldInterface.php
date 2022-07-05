<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use craft\base\ElementInterface;
use Twig\Markup;

use verbb\formie\elements\Form;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

interface FormFieldInterface extends ComponentInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_DEFAULT_VALUE = 'modifyDefaultValue';
    public const EVENT_MODIFY_HTML_TAG = 'modifyHtmlTag';
    public const EVENT_MODIFY_VALUE_AS_STRING = 'modifyValueAsString';
    public const EVENT_MODIFY_VALUE_AS_JSON = 'modifyValueAsJson';
    public const EVENT_MODIFY_VALUE_FOR_EXPORT = 'modifyValueForExport';
    public const EVENT_MODIFY_VALUE_FOR_INTEGRATION = 'modifyValueForIntegration';
    public const EVENT_MODIFY_VALUE_FOR_SUMMARY = 'modifyValueForSummary';
    public const EVENT_MODIFY_VALUE_FOR_EMAIL = 'modifyValueForEmail';


    // Public Methods
    // =========================================================================

    /**
     * Returns the SVG icon.
     *
     * @return string
     */
    public static function getSvgIcon(): string;

    /**
     * Returns the path to the SVG icon.
     *
     * @return string
     */
    public static function getSvgIconPath(): string;

    /**
     * Returns the template path for the frontend input HTML.
     *
     * @return string
     */
    public static function getFrontEndInputTemplatePath(): string;

    /**
     * Returns the template path for the frontend email HTML.
     *
     * @return string
     */
    public static function getEmailTemplatePath(): string;

    /**
     * Returns true if this field is an unsaved field
     * with a reference to another.
     *
     * @return bool
     */
    public function getIsRef(): bool;

    /**
     * Returns the nice submission value for this field.
     *
     * @param ElementInterface $element
     * @return mixed
     */
    public function getValue(ElementInterface $element): mixed;

    /**
     * Returns the default settings for new fields of this type.
     *
     * @return array
     */
    public function getFieldDefaults(): array;

    /**
     * Returns all the default settings for a field.
     *
     * @return array
     * @see FormFieldTrait::getFieldDefaults() to add aditional settings.
     */
    public function getAllFieldDefaults(): array;

    /**
     * Defines the schema for the edit field modal.
     *
     * @return array
     * @see FormFieldTrait::getFieldSchema()
     */
    public function getFieldSchema(): array;

    /**
     * Returns true if the field should show a label in the CP.
     *
     * @return bool
     */
    public function hasLabel(): bool;

    /**
     * Returns true if the field contains any sub-fields (Name, Address, etc).
     *
     * @return bool
     */
    public function hasSubfields(): bool;

    /**
     * Returns true if the field contains any nested fields (Group, Repeater, etc).
     *
     * @return bool
     */
    public function hasNestedFields(): bool;

    /**
     * Returns true if the field is considering cosmetic, and has no value (heading, section, etc).
     *
     * @return bool
     */
    public function getIsCosmetic(): bool;

    /**
     * Returns true if the field is considering hidden through visibility settings.
     *
     * @return bool
     */
    public function getIsHidden(): bool;

    /**
     * Returns any extra config items to be added to the
     * base field config.
     *
     * @return array
     */
    public function getExtraBaseFieldConfig(): array;

    /**
     * Returns a JSON safe array of settings for this field
     * for rendering the form builder.
     *
     * @return array
     */
    public function getBaseFieldConfig(): array;

    /**
     * Returns the fields saved settings for rendering the form builder.
     *
     * @return array
     */
    public function getSavedSettings(): array;

    /**
     * Returns custom container attributes.
     *
     * @return array
     */
    public function getContainerAttributes(): array;

    /**
     * Returns custom input attributes.
     *
     * @return array
     */
    public function getInputAttributes(): array;

    /**
     * Returns the preview HTML for rendering in the form builder.
     *
     * @return string
     */
    public function getPreviewInputHtml(): string;

    /**
     * Returns an array of options that will be passed into the render function.
     *
     * @param Form $form
     * @param mixed $value
     * @param array|null $renderOptions
     * @return array
     */
    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array;

    /**
     * Returns the frontend input HTML.
     *
     * @param Form $form
     * @param mixed $value
     * @param array|null $renderOptions
     * @return Markup
     */
    public function getFrontEndInputHtml(Form $form, mixed $value, array $renderOptions = []): Markup;

    /**
     * Returns an array of options that will be passed into the render function.
     *
     * @param Submission $submission
     * @param Notification $notification
     * @param mixed $value
     * @param array $renderOptions
     * @return array
     */
    public function getEmailOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array;

    /**
     * Gets the email HTML for this field.
     *
     * @param Submission $submission
     * @param Notification $notification
     * @param mixed $value
     * @param array|null $renderOptions
     * @return string|bool|null
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool;

    /**
     * Returns the namespace for this field.
     *
     * @return string
     */
    public function getNamespace(): string;

    /**
     * Returns the general schema for the field.
     *
     * @return array
     */
    public function defineGeneralSchema(): array;

    /**
     * Returns the settings schema for the field.
     *
     * @return array
     */
    public function defineSettingsSchema(): array;

    /**
     * Returns the appearance schema for the field.
     *
     * @return array
     */
    public function defineAppearanceSchema(): array;

    /**
     * Returns the advanced schema for the field.
     *
     * @return array
     */
    public function defineAdvancedSchema(): array;

    /**
     * Called after a field is created.
     *
     * @param array $data
     */
    public function afterCreateField(array $data);
}
