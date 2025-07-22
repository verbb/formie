<?php
namespace verbb\formie\variables;

use verbb\formie\Formie as FormiePlugin;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\PositionInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\helpers\Plugin;
use verbb\formie\helpers\Variables;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\Notification;
use verbb\formie\positions\AboveInput;

use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;

use yii\base\InvalidConfigException;

use Twig\Markup;
use Twig\Error\SyntaxError;
use Twig\Error\RuntimeError;
use Twig\Error\LoaderError;

use Exception;
use Throwable;

class Formie
{
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

    public function renderField(Form|string|null $form, FieldInterface|string $field, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderField($form, $field, $renderOptions);
    }

    public function registerAssets(Form|string|null $form, array $renderOptions = []): void
    {
        FormiePlugin::$plugin->getRendering()->registerAssets($form, $renderOptions);
    }

    public function renderFormCss(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderFormCss($form, $renderOptions);
    }

    public function renderFormJs(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderFormJs($form, $renderOptions);
    }

    public function renderCss(bool $inline = false, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderCss($inline, $renderOptions);
    }

    public function renderJs(bool $inline = false, array $renderOptions = []): ?Markup
    {
        return FormiePlugin::$plugin->getRendering()->renderJs($inline, $renderOptions);
    }

    public function getFieldOptions(FieldInterface $field, array $renderOptions = []): array
    {
        return FormiePlugin::$plugin->getFields()->getFieldOptions($field, $renderOptions);
    }

    public function getLabelPosition(FieldInterface $field, Form $form, bool $subField = false): PositionInterface
    {
        // A hard error will be thrown if the position class doesn't exist
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
        // A hard error will be thrown if the position class doesn't exist
        try {
            $position = $field->instructionsPosition ?: $form->settings->defaultInstructionsPosition;

            return new $position();
        } catch (Throwable $e) {
            return new AboveInput();
        }
    }

    public function getParsedValue(string $value, Submission $submission, Form $form = null, Notification $notification = null): ?string
    {
        return Variables::getParsedValue($value, $submission, $form, $notification);
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
        FormiePlugin::$plugin->getRendering()->getRenderVariables($key);
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
                'help-desk' => ['title' => Craft::t('formie', 'Help Desk')],
                'messaging' => ['title' => Craft::t('formie', 'Messaging')],
                'payments' => ['title' => Craft::t('formie', 'Payments')],
                'automations' => ['title' => Craft::t('formie', 'Automations')],
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
                'help-desk' => ['title' => Craft::t('formie', 'Help Desk')],
                'messaging' => ['title' => Craft::t('formie', 'Messaging')],
                'payments' => ['title' => Craft::t('formie', 'Payments')],
                'automations' => ['title' => Craft::t('formie', 'Automations')],
                'miscellaneous' => ['title' => Craft::t('formie', 'Miscellaneous')],
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

    public function getSubmissionRelations(ElementInterface $element): array
    {
        return FormiePlugin::$plugin->getRelations()->getSubmissionRelations($element);
    }

    public function getFieldNamespaceForScript(FieldInterface $field): string
    {
        return FormiePlugin::$plugin->getService()->getFieldNamespaceForScript($field);
    }

    public function getVisibleFields(FieldLayoutRow $row): array
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.formie.getVisibleFields()` has been deprecated. Use `row.getIsHidden()` instead.');

        $fields = [];

        foreach ($row->getFields(false) as $field) {
            if (!$field->getIsHidden()) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
