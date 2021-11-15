<?php
namespace verbb\formie\variables;

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
use verbb\formie\models\Notification;
use verbb\formie\services\Rendering;

use Craft;
use craft\errors\MissingComponentException;

use Twig\Markup;

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
     * @return array
     */
    public function getEmailTemplates(): array
    {
        return FormiePlugin::$plugin->getEmailTemplates()->getAllTemplates();
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
     * Registers assets for a form. This will not output anything.
     *
     * @param Form|string $form
     * @param array|null $options
     * @return null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function registerAssets($form, array $options = null)
    {
        return FormiePlugin::$plugin->getRendering()->registerAssets($form, $options);
    }

    public function renderFormCss($form, $attributes = [])
    {
        return FormiePlugin::$plugin->getRendering()->renderFormAssets($form, Rendering::RENDER_TYPE_CSS, $attributes);
    }

    public function renderFormJs($form, $attributes = [])
    {
        return FormiePlugin::$plugin->getRendering()->renderFormAssets($form, Rendering::RENDER_TYPE_JS, $attributes);
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
    public function getParsedValue($value, Submission $submission, Form $form = null, Notification $notification = null)
    {
        return Variables::getParsedValue($value, $submission, $form, $notification);
    }

    public function populateFormValues($form, $values)
    {
        return FormiePlugin::$plugin->getRendering()->populateFormValues($form, $values);
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

    /**
     * @return array
     */
    public function getSettingsNavItems(): array
    {
        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $navItems = [
                'general' => ['title' => Craft::t('formie', 'General Settings')],
                'import-export' => ['title' => Craft::t('formie', 'Import/Export')],
                'forms' => ['title' => Craft::t('formie', 'Forms')],
                'fields' => ['title' => Craft::t('formie', 'Fields')],
                
                'behavior-heading' => ['heading' => Craft::t('formie', 'Behavior')],
                'notifications' => ['title' => Craft::t('formie', 'Email Notifications')],
                'sent-notifications' => ['title' => Craft::t('formie', 'Sent Notifications')],
                'statuses' => ['title' => Craft::t('formie', 'Statuses')],
                'submissions' => ['title' => Craft::t('formie', 'Submissions')],
                'spam' => ['title' => Craft::t('formie', 'Spam')],
                // 'security' => ['title' => Craft::t('formie', 'Security')],
                // 'privacy' => ['title' => Craft::t('formie', 'Privacy & Data')],
                
                'appearance-heading' => ['heading' => Craft::t('formie', 'Appearance')],
                'stencils' => ['title' => Craft::t('formie', 'Stencils')],
                'form-templates' => ['title' => Craft::t('formie', 'Form Templates')],
                'email-templates' => ['title' => Craft::t('formie', 'Email Templates')],
                'pdf-templates' => ['title' => Craft::t('formie', 'PDF Templates')],
                
                'integrations-heading' => ['heading' => Craft::t('formie', 'Integrations')],
                'captchas' => ['title' => Craft::t('formie', 'Captchas')],
                'address-providers' => ['title' => Craft::t('formie', 'Address Providers')],
                'elements' => ['title' => Craft::t('formie', 'Elements')],
                'email-marketing' => ['title' => Craft::t('formie', 'Email Marketing')],
                'crm' => ['title' => Craft::t('formie', 'CRM')],
                'webhooks' => ['title' => Craft::t('formie', 'Webhooks')],
                'miscellaneous' => ['title' => Craft::t('formie', 'Miscellaneous')],
            ];
        } else {
            $navItems = [
                'import-export' => ['title' => Craft::t('formie', 'Import/Export')],
            
                'integrations-heading' => ['heading' => Craft::t('formie', 'Integrations')],
                'address-providers' => ['title' => Craft::t('formie', 'Address Providers')],
                'elements' => ['title' => Craft::t('formie', 'Elements')],
                'email-marketing' => ['title' => Craft::t('formie', 'Email Marketing')],
                'crm' => ['title' => Craft::t('formie', 'CRM')],
                'webhooks' => ['title' => Craft::t('formie', 'Webhooks')],
                'miscellaneous' => ['title' => Craft::t('formie', 'Miscellaneous')],
            ];
        }

        $plugins = [];

        if (FormiePlugin::$plugin->getService()->isPluginInstalledAndEnabled('freeform')) {
            $plugins['migrate/freeform'] = ['title' => Craft::t('formie', 'Freeform')];
        }

        if (FormiePlugin::$plugin->getService()->isPluginInstalledAndEnabled('sprout-forms')) {
            $plugins['migrate/sprout-forms'] = ['title' => Craft::t('formie', 'Sprout Forms')];
        }

        if ($plugins) {
            $navItems['migrations-heading'] = ['heading' => Craft::t('formie', 'Migrations')];
            $navItems = array_merge($navItems, $plugins);
        }

        $navItems['support-heading'] = ['heading' => Craft::t('formie', 'Support')];
        $navItems['support'] = ['title' => Craft::t('formie', 'Get Support')];

        return $navItems;
    }

    /**
     * @return array
     */
    public function getVisibleFields($row): array
    {
        $fields = [];
        $rowFields = $row['fields'] ?? [];

        foreach ($rowFields as $field) {
            if (!$field->getIsHidden()) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function getSubmissionRelations($element)
    {
        return FormiePlugin::$plugin->getRelations()->getSubmissionRelations($element);
    }

    /**
     * @inheritdoc
     */
    public function getFieldNamespaceForScript($field)
    {
        return FormiePlugin::$plugin->getService()->getFieldNamespaceForScript($field);
    }
}
