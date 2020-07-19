<?php
namespace verbb\formie\variables;

use Craft;

use craft\errors\MissingComponentException;
use Twig\Markup;

use verbb\formie\Formie as FormiePlugin;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\PositionInterface;
use verbb\formie\base\SubfieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayoutPage;

class Formie
{
    /**
     * @return array
     */
    public function getStatuses(): array
    {
        return FormiePlugin::$plugin->getStatuses()->getAllStatuses();
    }

    /**
     * @return array
     */
    public function getTemplates(): array
    {
        return FormiePlugin::$plugin->getFormTemplates()->getAllTemplates();
    }

    /**
     * @param null $criteria
     * @return FormQuery
     */
    public function forms($criteria = null): FormQuery
    {
        $query = Form::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        /* @var FormQuery $query */
        return $query;
    }

    /**
     * @param null $criteria
     * @return SubmissionQuery
     */
    public function submissions($criteria = null): SubmissionQuery
    {
        $query = Submission::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        /* @var SubmissionQuery $query */
        return $query;
    }

    /**
     * Sets the current submission for the provided form.
     *
     * @param Form $form
     * @param Submission|null $submission
     * @throws MissingComponentException
     */
    public function setCurrentSubmission(Form $form, $submission)
    {
        $form->setCurrentSubmission($submission);
    }

    /**
     * Renders a form.
     *
     * @param Form|string $form
     * @param array|null $options
     * @return Markup|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function renderForm($form, array $options = null)
    {
        return FormiePlugin::$plugin->getRendering()->renderForm($form, $options);
    }

    /**
     * Renders a form page.
     *
     * @param Form $form
     * @param FieldLayoutPage $page
     * @param array|null $options
     * @return Markup|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function renderPage(Form $form, FieldLayoutPage $page = null, array $options = null)
    {
        return FormiePlugin::$plugin->getRendering()->renderPage($form, $page, $options);
    }

    /**
     * Renders a form field.
     *
     * @param Form $form
     * @param FormFieldInterface|FormFieldTrait $field
     * @param array|null $options
     * @return Markup|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function renderField(Form $form, $field, array $options = null)
    {
        return FormiePlugin::$plugin->getRendering()->renderField($form, $field, $options);
    }

    /**
     * Gets a field's options from the main options array.
     *
     * @param FormFieldInterface|FormFieldTrait $field
     * @param array|null $options
     * @return array
     */
    public function getFieldOptions($field, array $options = null): array
    {
        return FormiePlugin::$plugin->getFields()->getFieldOptions($field, $options);
    }

    /**
     * Returns a fields label position.
     *
     * @param FormFieldInterface|FormFieldTrait|SubfieldTrait $field
     * @param Form $form
     * @param bool $subfield
     * @return PositionInterface
     */
    public function getLabelPosition($field, Form $form, bool $subfield = false): PositionInterface
    {
        /* @var PositionInterface $position */
        $position = $subfield && $field->hasSubfields() ? $field->subfieldLabelPosition : $field->labelPosition;
        $position = $position ?: $form->settings->defaultLabelPosition;

        if (!$position::supports($field) && $fallback = $position::fallback($field)) {
            return new $fallback();
        }

        return new $position();
    }

    /**
     * Returns a fields instructions position.
     *
     * @param FormFieldInterface|FormFieldTrait $field
     * @param Form $form
     * @return PositionInterface
     */
    public function getInstructionsPosition($field, Form $form): PositionInterface
    {
        $position = $field->instructionsPosition ?: $form->settings->defaultInstructionsPosition;
        return new $position();
    }

    /**
     * Renders and returns an object template, or null if it fails.
     *
     * @param string $value
     * @param Submission $submission
     * @param Form $form
     * @return string|null
     */
    public function getParsedValue($value, Submission $submission, Form $form = null)
    {
        return Variables::getParsedValue($value, $submission, $form);
    }

    /**
     * @return FormiePlugin
     */
    public function getPlugin(): FormiePlugin
    {
        return FormiePlugin::$plugin;
    }

    /**
     * @return string
     */
    public function getPluginName(): string
    {
        return FormiePlugin::$plugin->getSettings()->pluginName;
    }
}
