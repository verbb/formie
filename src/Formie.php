<?php
namespace verbb\formie;

use Craft;
use craft\base\Plugin;
use craft\events\FieldLayoutEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Gc;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gql;
use craft\services\ProjectConfig;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;

use verbb\formie\models\FieldLayout;
use yii\base\Event;

use verbb\formie\base\PluginTrait;
use verbb\formie\base\Routes;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Forms;
use verbb\formie\fields\Submissions;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\interfaces\PageSettingsInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\helpers\ProjectConfigHelper;
use verbb\formie\models\Settings;
use verbb\formie\services\Statuses as StatusesService;
use verbb\formie\services\Stencils as StencilsService;
use verbb\formie\services\FormTemplates as FormTemplatesService;
use verbb\formie\services\EmailTemplates as EmailTemplatesService;
use verbb\formie\variables\Formie as FormieVariable;
use verbb\formie\web\twig\Extension;

class Formie extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.5';
    public $hasCpSettings = true;
    public $hasCpSection = true;


    // Traits
    // =========================================================================

    use PluginTrait;
    use Routes;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerTwigExtensions();
        $this->_registerFieldsEvents();
        $this->_registerFieldTypes();
        $this->_registerPermissions();
        $this->_registerVariable();
        $this->_registerElementTypes();
        $this->_registerGarbageCollection();
        $this->_registerGraphQl();
        $this->_registerProjectConfigEventListeners();
        $this->_registerEmailMessages();

        // Add default captcha integrations
        Craft::$app->view->hook('formie.form.beforeSubmit', static function(array &$context) {
            return Formie::$plugin->getForms()->handleBeforeSubmitHook($context);
        });
    }

    public function getPluginName()
    {
        return Craft::t('formie', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('formie/settings'));
    }

    public function getCpNavItem(): array
    {
        $nav = parent::getCpNavItem();

        $nav['label'] = $this->getPluginName();

        if (Craft::$app->getUser()->checkPermission('formie-manageForms')) {
            $nav['subnav']['forms'] = [
                'label' => Craft::t('formie', 'Forms'),
                'url' => 'formie/forms',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('formie-viewSubmissions') || Craft::$app->getUser()->checkPermission('formie-editSubmissions')) {
            $nav['subnav']['submissions'] = [
                'label' => Craft::t('formie', 'Submissions'),
                'url' => 'formie/submissions',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['settings'] = [
                'label' => Craft::t('formie', 'Settings'),
                'url' => 'formie/settings',
            ];
        }

        return $nav;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerTwigExtensions()
    {
        Craft::$app->view->registerTwigExtension(new Extension);
    }

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Formie'] = [
                'formie-manageForms' => ['label' => Craft::t('formie', 'Manage forms'), 'nested' => [
                    'formie-editForms' => ['label' => Craft::t('formie', 'Edit forms')],
                    'formie-manageFormAppearance' => ['label' => Craft::t('formie', 'Manage appearance')],
                    'formie-manageFormBehavior' => ['label' => Craft::t('formie', 'Manage behavior')],
                    'formie-manageNotifications' => ['label' => Craft::t('formie', 'Manage notifications'),  'nested' => [
                        'formie-manageNotificationsAdvanced' => ['label' => Craft::t('formie', 'Manage advanced')],
                        'formie-manageNotificationsTemplates' => ['label' => Craft::t('formie', 'Manage templates')],
                    ]],
                    'formie-manageFormIntegrations' => ['label' => Craft::t('formie', 'Manage integrations')],
                    'formie-manageFormSettings' => ['label' => Craft::t('formie', 'Manage settings')],
                ]],
                'formie-viewSubmissions' => ['label' => Craft::t('formie', 'View submissions'), 'nested' => [
                    'formie-editSubmissions' => ['label' => Craft::t('formie', 'Edit submissions')],
                ]],
            ];
        });
    }

    private function _registerVariable()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('formie', FormieVariable::class);
        });
    }

    private function _registerElementTypes()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Form::class;
            $event->types[] = Submission::class;
        });
    }

    private function _registerFieldsEvents()
    {
        Event::on(Fields::class, Fields::EVENT_AFTER_SAVE_FIELD_LAYOUT, function (FieldLayoutEvent $event) {
            $fieldLayout = $event->layout;

            if ($fieldLayout instanceof FieldLayout) {
                /* @var FieldLayout $fieldLayout */
                Formie::$plugin->getFields()->onSaveFieldLayout($fieldLayout);
            }
        });
    }

    private function _registerFieldTypes()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Forms::class;
            $event->types[] = Submissions::class;
        });
    }

    private function _registerGarbageCollection()
    {
        Event::on(Gc::class, Gc::EVENT_RUN, function () {
            // Delete fields with no form.
            $this->getFields()->deleteOrphanedFields();

            // Delete syncs that are empty.
            $this->getSyncs()->pruneSyncs();

            // Delete incomplete submissions older than the configured interval.
            $this->getSubmissions()->pruneSubmissions();

            // Delete leftover content tables, for deleted forms
            $this->getForms()->pruneContentTables();
        });
    }

    private function _registerGraphQl()
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypesEvent $event) {
            $event->types[] = FormInterface::class;
            $event->types[] = PageInterface::class;
            $event->types[] = PageSettingsInterface::class;
            $event->types[] = RowInterface::class;
            $event->types[] = FieldInterface::class;
            $event->types[] = SubmissionInterface::class;
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $queries = [
                FormQuery::getQueries(),
                SubmissionQuery::getQueries(),
            ];
            
            foreach ($queries as $k => $v) {
                foreach ($v as $key => $value) {
                    $event->queries[$key] = $value;
                }
            }
        });
    }

    private function _registerProjectConfigEventListeners()
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $statusesService = $this->getStatuses();
        $projectConfigService
            ->onAdd(StatusesService::CONFIG_STATUSES_KEY . '.{uid}', [$statusesService, 'handleChangedStatus'])
            ->onUpdate(StatusesService::CONFIG_STATUSES_KEY . '.{uid}', [$statusesService, 'handleChangedStatus'])
            ->onRemove(StatusesService::CONFIG_STATUSES_KEY . '.{uid}', [$statusesService, 'handleDeletedStatus']);

        $stencilsService = $this->getStencils();
        $projectConfigService
            ->onAdd(StencilsService::CONFIG_STENCILS_KEY . '.{uid}', [$stencilsService, 'handleChangedStencil'])
            ->onUpdate(StencilsService::CONFIG_STENCILS_KEY . '.{uid}', [$stencilsService, 'handleChangedStencil'])
            ->onRemove(StencilsService::CONFIG_STENCILS_KEY . '.{uid}', [$stencilsService, 'handleDeletedStencil']);

        $formTemplatesService = $this->getFormTemplates();
        $projectConfigService
            ->onAdd(FormTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$formTemplatesService, 'handleChangedTemplate'])
            ->onUpdate(FormTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$formTemplatesService, 'handleChangedTemplate'])
            ->onRemove(FormTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$formTemplatesService, 'handleDeletedTemplate']);

        $emailTemplatesService = $this->getEmailTemplates();
        $projectConfigService
            ->onAdd(EmailTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$emailTemplatesService, 'handleChangedTemplate'])
            ->onUpdate(EmailTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$emailTemplatesService, 'handleChangedTemplate'])
            ->onRemove(EmailTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$emailTemplatesService, 'handleDeletedTemplate']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['formie'] = ProjectConfigHelper::rebuildProjectConfig();
        });
    }

    private function _registerEmailMessages()
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
            $event->messages = array_merge($event->messages, [
                [
                    'key' => 'formie_failed_notification',
                    'heading' => Craft::t('formie', 'formie_failed_notification_heading'),
                    'subject' => Craft::t('formie', 'formie_failed_notification_subject'),
                    'body' => Craft::t('formie', 'formie_failed_notification_body'),
                ],
            ]);
        });
    }
}
