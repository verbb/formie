<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;
use craft\base\ElementInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;

use verbb\formie\elements\Form;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;
use yii\base\Exception;

interface FormFieldInterface extends ComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns true if this field is an unsaved field
     * with a reference to another.
     *
     * @return bool
     */
    public function getIsRef(): bool;

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
     * Returns the nice submission value for this field.
     *
     * @param ElementInterface $element
     * @return mixed
     */
    public function getValue(ElementInterface $element);

    /**
     * Returns the default settings for new fields of this type.
     *
     * @return array
     */
    public function getFieldDefaults(): array;

    /**
     * Returns all the default settings for a field.
     *
     * @see FormFieldTrait::getFieldDefaults() to add aditional settings.
     * @return array
     */
    public function getAllFieldDefaults(): array;

    /**
     * Defines the schema for the edit field modal.
     *
     * @see FormFieldTrait::getFieldSchema()
     * @return array
     */
    public function getFieldSchema(): array;

    /**
     * Returns true if the field should show a label in the CP.
     *
     * @return bool
     */
    public function hasLabel(): bool;

    /**
     * Returns true if the field should render the label in the form.
     *
     * @return bool
     */
    public function renderLabel(): bool;

    /**
     * Returns true if the field appears as a text input, i.e. text, password
     * and email inputs.
     *
     * @return bool
     */
    public function getIsTextInput(): bool;

    /**
     * Returns true if the field appears as a dropdown select box (not multiple).
     *
     * @return bool
     */
    public function getIsSelect(): bool;

    /**
     * Returns true if the field comprises of multiple inputs.
     *
     * @return bool
     */
    public function getIsFieldset(): bool;

    /**
     * Returns any extra config items to be added the the
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
     * @param array|null $options
     * @return array
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array;

    /**
     * Returns the frontend input HTML.
     *
     * @param Form $form
     * @param mixed $value
     * @param array|null $options
     * @return Markup
     */
    public function getFrontEndInputHtml(Form $form, $value, array $options = null): Markup;

    /**
     * Returns an array of options that will be passed into the render function.
     *
     * @param Submission $submission
     * @param mixed $value
     * @param array|null $options
     * @return array
     */
    public function getEmailOptions(Submission $submission, $value, array $options = null): array;

    /**
     * Gets the email HTML for this field.
     *
     * @param Submission $submission
     * @param mixed $value
     * @param array|null $options
     * @return Markup
     */
    public function getEmailHtml(Submission $submission, $value, array $options = null);

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
