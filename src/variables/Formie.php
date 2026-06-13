<?php
namespace verbb\formie\variables;

use verbb\formie\Formie as FormiePlugin;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\PositionInterface;
use verbb\formie\deprecations\FormieVariableDeprecations;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\References;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\Notification;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;

use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
use craft\helpers\Template as TemplateHelper;

use yii\base\InvalidConfigException;

use Twig\Markup;
use Twig\Error\SyntaxError;
use Twig\Error\RuntimeError;
use Twig\Error\LoaderError;

use Exception;
use Throwable;

class Formie
{
    // Traits
    // =========================================================================

    use FormieVariableDeprecations;
    

    // Public Methods
    // =========================================================================

    public function getStatuses(): array
    {
        return FormiePlugin::$plugin->getStatuses()->getAllStatuses();
    }

    public function getTemplates(): array
    {
        return FormiePlugin::$plugin->getFormTemplates()->getAllTemplates();
    }

    public function getEmailTemplates(): array
    {
        return FormiePlugin::$plugin->getEmailTemplates()->getAllTemplates();
    }

    public function forms(array $criteria = []): FormQuery
    {
        $query = Form::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function submissions(array $criteria = []): SubmissionQuery
    {
        $query = Submission::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function setCurrentSubmission(Form $form, ?Submission $submission): void
    {
        $form->setCurrentSubmission($submission);
    }

    public function renderForm(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderForm($form, $renderOptions);
    }

    public function renderPage(Form|string|null $form, FieldLayoutPage $page = null, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderPage($form, $page, $renderOptions);
    }

    public function renderField(Form|string|null $form, FieldInterface|string $field, array $fieldOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderField($form, $field, $fieldOptions);
    }

    public function renderCaptchas(Form|string|null $form, FieldLayoutPage $page = null): ?Markup
    {
        if (!$form) {
            return null;
        }

        if (is_string($form)) {
            $form = FormiePlugin::$plugin->getForms()->getFormByHandle($form);
        }

        if (!$form) {
            return null;
        }

        return TemplateHelper::raw(FormiePlugin::$plugin->getIntegrations()->getCaptchasHtmlForForm($form, $page));
    }

    public function formAssets(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->formAssets($form, $renderOptions);
    }

    public function frontendAssets(array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->frontendAssets($renderOptions);
    }

    public function getFieldOptions(FieldInterface $field, array $renderOptions = []): array
    {
        return FormiePlugin::$plugin->getFields()->getFieldOptions($field, $renderOptions);
    }

    public function getLabelPosition(FieldInterface $field, Form $form, bool $subField = false): PositionInterface
    {
        // Theme/layout configs can reference custom position classes. Degrade to
        // a safe default in Twig rather than letting one bad class break form
        // rendering for the whole request.
        try {
            /* @var PositionInterface $position */
            $position = $subField && $field->hasSubFields() ? $field->subFieldLabelPosition : $field->labelPosition;
            $position = $position ?: $form->settings->defaultLabelPosition;

            if (!$position::supports($field) && $fallback = $position::fallback($field)) {
                return new $fallback();
            }

            return new $position();
        } catch (Throwable $e) {
            return new AboveInput();
        }
    }

    public function getInstructionsPosition(FieldInterface $field, Form $form): PositionInterface
    {
        // Mirror the label-position fallback so authoring mistakes in per-field
        // instruction positions do not take down the rendered form.
        try {
            $position = $field->instructionsPosition ?: $form->settings->defaultInstructionsPosition;

            return new $position();
        } catch (Throwable $e) {
            return new AboveInput();
        }
    }

    public function getErrorMessagePosition(FieldInterface $field, Form $form): PositionInterface
    {
        try {
            $position = $field->errorMessagePosition ?: $form->settings->defaultErrorMessagePosition;

            return new $position();
        } catch (Throwable $e) {
            return new BelowInput();
        }
    }

    public function parseValue(mixed $value, Submission $submission, array $options = []): mixed
    {
        return References::parseValue($value, $submission, $options);
    }

    public function parseContent(string $content, Submission $submission, array $options = []): string
    {
        return References::parseContent($content, $submission, $options);
    }

    public function populateFormValues(Form $element, $values, $force = false): void
    {
        FormiePlugin::$plugin->getRendering()->populateFormValues($element, $values, $force);
    }

    public function setRenderVariables(array $variables = []): void
    {
        FormiePlugin::$plugin->getRendering()->setRenderVariables($variables);
    }

    public function getRenderVariables(string $key): mixed
    {
        return FormiePlugin::$plugin->getRendering()->getRenderVariables($key);
    }

    public function getPlugin(): FormiePlugin
    {
        return FormiePlugin::$plugin;
    }

    public function getPluginName(): string
    {
        return FormiePlugin::$plugin->getSettings()->pluginName;
    }

    public function getSettingsNavItems(): array
    {
        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $navItems = [
                'general' => ['title' => Craft::t('formie', 'General Settings')],
                'import-export' => ['title' => Craft::t('formie', 'Import/Export')],
                'forms' => ['title' => Craft::t('formie', 'Forms')],
                'form-groups' => ['title' => Craft::t('formie', 'Form Groups')],
                'synced-fields' => ['title' => Craft::t('formie', 'Synced Fields')],
                'defaults' => ['title' => Craft::t('formie', 'Defaults')],
                'fields' => ['title' => Craft::t('formie', 'Fields')],

                'behavior-heading' => ['heading' => Craft::t('formie', 'Behavior')],
                'notifications' => ['title' => Craft::t('formie', 'Email Notifications')],
                'sent-notifications' => ['title' => Craft::t('formie', 'Sent Notifications')],
                'statuses' => ['title' => Craft::t('formie', 'Statuses')],
                'submissions' => ['title' => Craft::t('formie', 'Submissions')],
                'integrations-settings' => ['title' => Craft::t('formie', 'Integrations')],
                'spam' => ['title' => Craft::t('formie', 'Spam')],

                'appearance-heading' => ['heading' => Craft::t('formie', 'Appearance')],
                'form-templates' => ['title' => Craft::t('formie', 'Form Templates')],
                'email-templates' => ['title' => Craft::t('formie', 'Email Templates')],
                'pdf-templates' => ['title' => Craft::t('formie', 'PDF Templates')],

                'integrations-heading' => ['heading' => Craft::t('formie', 'Integrations')],
                'captchas' => ['title' => Craft::t('formie', 'Captchas')],
            ];
        } else {
            $navItems = [
                'import-export' => ['title' => Craft::t('formie', 'Import/Export')],

                'integrations-heading' => ['heading' => Craft::t('formie', 'Integrations')],
                'captchas' => ['title' => Craft::t('formie', 'Captchas')],
            ];
        }

        $plugins = [];

        if (Plugin::isPluginInstalledAndEnabled('freeform')) {
            $plugins['migrate/freeform4'] = ['title' => Craft::t('formie', 'Freeform 4')];
            $plugins['migrate/freeform5'] = ['title' => Craft::t('formie', 'Freeform 5')];
        }

        if (Plugin::isPluginInstalledAndEnabled('sprout-forms')) {
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

    public function getIntegrationsNavItems(): array
    {
        return [
            'address-providers' => ['title' => Craft::t('formie', 'Address Providers')],
            'elements' => ['title' => Craft::t('formie', 'Elements')],
            'email-marketing' => ['title' => Craft::t('formie', 'Email Marketing')],
            'crm' => ['title' => Craft::t('formie', 'CRM')],
            'help-desk' => ['title' => Craft::t('formie', 'Help Desk')],
            'messaging' => ['title' => Craft::t('formie', 'Messaging')],
            'payments' => ['title' => Craft::t('formie', 'Payments')],
            'automations' => ['title' => Craft::t('formie', 'Automations')],
            'miscellaneous' => ['title' => Craft::t('formie', 'Miscellaneous')],
        ];
    }

    public function getSubmissionRelations(ElementInterface $element): array
    {
        return FormiePlugin::$plugin->getRelations()->getSubmissionRelations($element);
    }

    public function getFieldNamespaceForScript(FieldInterface $field): string
    {
        return FormiePlugin::$plugin->getService()->getFieldNamespaceForScript($field);
    }

}
