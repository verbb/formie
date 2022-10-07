<?php
namespace verbb\formie;

use verbb\formie\base\PluginTrait;
use verbb\formie\base\Routes;
use verbb\formie\elements\Form;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\elements\exporters\SubmissionExport;
use verbb\formie\fields\Forms;
use verbb\formie\fields\Submissions;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\interfaces\PageSettingsInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\helpers\Gql as GqlHelper;
use verbb\formie\helpers\ProjectConfigHelper;
use verbb\formie\integrations\feedme\elements\Submission as FeedMeSubmission;
use verbb\formie\jobs\BaseJob;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Settings;
use verbb\formie\services\EmailTemplates as EmailTemplatesService;
use verbb\formie\services\FormTemplates as FormTemplatesService;
use verbb\formie\services\Integrations as IntegrationsService;
use verbb\formie\services\PdfTemplates as PdfTemplatesService;
use verbb\formie\services\Statuses as StatusesService;
use verbb\formie\services\Stencils as StencilsService;
use verbb\formie\variables\Formie as FormieVariable;
use verbb\formie\web\twig\Extension;
use verbb\formie\widgets\RecentSubmissions;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\controllers\UsersController;
use craft\console\Application as ConsoleApplication;
use craft\console\Controller as ConsoleController;
use craft\console\controllers\ResaveController;
use craft\elements\User as UserElement;
use craft\events\DefineConsoleActionsEvent;
use craft\events\FieldLayoutEvent;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementExportersEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\queue\Queue;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\Gql;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;

use craft\gatsbyhelper\events\RegisterSourceNodeTypesEvent;
use craft\gatsbyhelper\services\SourceNodes;

use craft\feedme\events\RegisterFeedMeElementsEvent;
use craft\feedme\events\RegisterFeedMeFieldsEvent;
use craft\feedme\services\Elements as FeedMeElements;
use craft\feedme\services\Fields as FeedMeFields;

use yii\base\Event;
use yii\queue\ExecEvent;
use craft\elements\exporters\Expanded;
use craft\elements\exporters\Raw;

class Formie extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '2.0.8';
    public string $minVersionRequired = '1.5.15';


    // Traits
    // =========================================================================

    use PluginTrait;
    use Routes;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerComponents();
        $this->_registerLogTarget();
        $this->_registerTwigExtensions();
        $this->_registerFieldsEvents();
        $this->_registerFieldTypes();
        $this->_registerVariable();
        $this->_registerElementTypes();
        $this->_registerGarbageCollection();
        $this->_registerGraphQl();
        $this->_registerCraftEventListeners();
        $this->_registerProjectConfigEventListeners();
        $this->_registerEmailMessages();
        $this->_registerTemplateRoots();
        $this->_registerThirdPartyEventListeners();
        $this->_registerTemplateHooks();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
            $this->_registerWidgets();
            $this->_registerElementExports();
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->_registerResaveCommand();
        }

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_registerSiteRoutes();
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
        }
    }

    public function getPluginName(): string
    {
        return Craft::t('formie', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('formie/settings'));
    }

    public function getCpNavItem(): ?array
    {
        $nav = parent::getCpNavItem();

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        $nav['label'] = $this->getPluginName();

        if (Craft::$app->getUser()->checkPermission('formie-viewForms')) {
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

        if (Craft::$app->getUser()->checkPermission('formie-viewSentNotifications') && $settings->sentNotifications) {
            $nav['subnav']['sentNotifications'] = [
                'label' => Craft::t('formie', 'Sent Notifications'),
                'url' => 'formie/sent-notifications',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $nav['subnav']['settings'] = [
                'label' => Craft::t('formie', 'Settings'),
                'url' => 'formie/settings',
            ];
        }

        return $nav;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new Extension);
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $formPermissions = [
                'formie-createForms' => ['label' => Craft::t('formie', 'Create forms')],
                'formie-deleteForms' => ['label' => Craft::t('formie', 'Delete forms')],
                'formie-editForms' => ['label' => Craft::t('formie', 'Manage all forms'), 'info' => Craft::t('formie', 'This user will be able to manage all Formie forms.')],
                'formie-manageFormAppearance' => ['label' => Craft::t('formie', 'Manage form appearance'), 'info' => Craft::t('formie', 'This permission will be applied to new forms automatically.')],
                'formie-manageFormBehavior' => ['label' => Craft::t('formie', 'Manage form behavior'), 'info' => Craft::t('formie', 'This permission will be applied to new forms automatically.')],
                'formie-manageNotifications' => [
                    'label' => Craft::t('formie', 'Manage form notifications'), 'nested' => [
                        'formie-manageNotificationsAdvanced' => ['label' => Craft::t('formie', 'Manage notification advanced'), 'info' => Craft::t('formie', 'This permission will be applied to new forms automatically.')],
                        'formie-manageNotificationsTemplates' => ['label' => Craft::t('formie', 'Manage notification templates'), 'info' => Craft::t('formie', 'This permission will be applied to new forms automatically.')],
                    ],
                ],
                'formie-manageFormIntegrations' => ['label' => Craft::t('formie', 'Manage form integrations'), 'info' => Craft::t('formie', 'This permission will be applied to new forms automatically.')],
                'formie-manageFormSettings' => ['label' => Craft::t('formie', 'Manage form settings'), 'info' => Craft::t('formie', 'This permission will be applied to new forms automatically.')],
            ];

            $submissionPermissions = [
                'formie-createSubmissions' => ['label' => Craft::t('formie', 'Create submissions')],
                'formie-editSubmissions' => ['label' => Craft::t('formie', 'Manage all submissions')],
            ];

            foreach (Form::find()->all() as $form) {
                $suffix = ':' . $form->uid;

                $formPermissions["formie-manageForm{$suffix}"] = [
                    'label' => Craft::t('formie', 'Manage “{name}” form', ['name' => $form->title]),
                    'nested' => [
                        "formie-manageFormAppearance{$suffix}" => ['label' => Craft::t('formie', 'Manage form appearance')],
                        "formie-manageFormBehavior{$suffix}" => ['label' => Craft::t('formie', 'Manage form behavior')],
                        "formie-manageNotifications{$suffix}" => [
                            'label' => Craft::t('formie', 'Manage form notifications'), 'nested' => [
                                "formie-manageNotificationsAdvanced{$suffix}" => ['label' => Craft::t('formie', 'Manage notification advanced')],
                                "formie-manageNotificationsTemplates{$suffix}" => ['label' => Craft::t('formie', 'Manage notification templates')],
                            ],
                        ],
                        "formie-manageFormIntegrations{$suffix}" => ['label' => Craft::t('formie', 'Manage form integrations')],
                        "formie-manageFormUsage{$suffix}" => ['label' => Craft::t('formie', 'View form usage')],
                        "formie-manageFormSettings{$suffix}" => ['label' => Craft::t('formie', 'Manage form settings')],
                    ],
                ];

                $submissionPermissions["formie-manageSubmission{$suffix}"] = [
                    'label' => Craft::t('formie', 'Manage “{name}” submissions', ['name' => $form->title]),
                ];
            }

            $event->permissions[] = [
                'heading' => Craft::t('formie', 'Formie'),
                'permissions' => [
                    'formie-viewForms' => ['label' => Craft::t('formie', 'View forms'), 'nested' => $formPermissions],
                    'formie-viewSubmissions' => ['label' => Craft::t('formie', 'View submissions'), 'nested' => $submissionPermissions],
                    'formie-viewSentNotifications' => ['label' => Craft::t('formie', 'View sent notifications')],
                ],
            ];
        });
    }

    private function _registerVariable(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('formie', FormieVariable::class);
        });
    }

    private function _registerElementTypes(): void
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Form::class;
            $event->types[] = SentNotification::class;
            $event->types[] = Submission::class;
        });
    }

    private function _registerFieldsEvents(): void
    {
        Event::on(Fields::class, Fields::EVENT_AFTER_SAVE_FIELD_LAYOUT, function(FieldLayoutEvent $event) {
            $fieldLayout = $event->layout;

            if ($fieldLayout instanceof FieldLayout) {
                /* @var FieldLayout $fieldLayout */
                Formie::$plugin->getFields()->onSaveFieldLayout($fieldLayout);
            }
        });
    }

    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Forms::class;
            $event->types[] = Submissions::class;
        });
    }

    private function _registerGarbageCollection(): void
    {
        Event::on(Gc::class, Gc::EVENT_RUN, function() {
            // Delete fields with no form.
            $this->getFields()->deleteOrphanedFields();

            // Delete syncs that are empty.
            $this->getSyncs()->pruneSyncs();

            // Delete incomplete submissions older than the configured interval.
            $this->getSubmissions()->pruneIncompleteSubmissions();

            // Deletes submissions if they are past the form data retention settings.
            $this->getSubmissions()->pruneDataRetentionSubmissions();

            // Delete leftover content tables, for deleted forms
            $this->getForms()->pruneContentTables();

            // Delete sent notifications older than the configured interval.
            $this->getSentNotifications()->pruneSentNotifications();
        });
    }

    private function _registerGraphQl(): void
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

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_MUTATIONS, function(RegisterGqlMutationsEvent $event) {
            $mutations = [
                SubmissionMutation::getMutations(),
            ];

            foreach ($mutations as $k => $v) {
                foreach ($v as $key => $value) {
                    $event->mutations[$key] = $value;
                }
            }
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS, function(RegisterGqlSchemaComponentsEvent $event) {
            $label = Craft::t('formie', 'Formie');

            $forms = Form::find()->all();

            $event->queries[$label]['formieForms.all:read'] = ['label' => Craft::t('formie', 'View all forms')];

            foreach ($forms as $form) {
                $suffix = 'formieForms.' . $form->uid;
                $event->queries[$label][$suffix . ':read'] = ['label' => Craft::t('formie', 'View “{form}” form', ['form' => Craft::t('formie', $form->title)])];
            }

            $event->queries[$label]['formieSubmissions.all:read'] = ['label' => Craft::t('formie', 'View all submissions')];

            foreach ($forms as $form) {
                $suffix = 'formieSubmissions.' . $form->uid;
                $event->queries[$label][$suffix . ':read'] = ['label' => Craft::t('formie', 'View submissions for form “{form}”', ['form' => Craft::t('formie', $form->title)])];
            }

            $event->mutations[$label]['formieSubmissions.all:edit'] = [
                'label' => Craft::t('formie', 'Edit all submissions'),
                'nested' => [
                    'formieSubmissions.all:create' => ['label' => Craft::t('app', 'Create all submissions')],
                    'formieSubmissions.all:save' => ['label' => Craft::t('app', 'Modify all submissions')],
                    'formieSubmissions.all:delete' => ['label' => Craft::t('app', 'Delete all submissions')],
                ],
            ];

            foreach ($forms as $form) {
                $suffix = 'formieSubmissions.' . $form->uid;
                $event->mutations[$label][$suffix . ':edit'] = [
                    'label' => Craft::t('formie', 'Edit submissions for form “{form}”', ['form' => Craft::t('formie', $form->title)]),
                    'nested' => [
                        $suffix . ':create' => ['label' => Craft::t('app', 'Create submissions for form “{form}”', ['form' => Craft::t('formie', $form->title)])],
                        $suffix . ':save' => ['label' => Craft::t('app', 'Modify submissions for form “{form}”', ['form' => Craft::t('formie', $form->title)])],
                        $suffix . ':delete' => ['label' => Craft::t('app', 'Delete submissions for form “{form}”', ['form' => Craft::t('formie', $form->title)])],
                    ],
                ];
            }
        });
    }

    private function _registerCraftEventListeners(): void
    {
        Event::on(UsersController::class, UsersController::EVENT_DEFINE_CONTENT_SUMMARY, [$this->getSubmissions(), 'defineUserSubmissions']);
        Event::on(UserElement::class, UserElement::EVENT_AFTER_DELETE, [$this->getSubmissions(), 'deleteUserSubmissions']);
        Event::on(UserElement::class, UserElement::EVENT_AFTER_RESTORE, [$this->getSubmissions(), 'restoreUserSubmissions']);

        // Add additional error information to queue jobs when there's an error
        Event::on(Queue::class, Queue::EVENT_AFTER_ERROR, function(ExecEvent $event) {
            if ($event->error && $event->job instanceof BaseJob) {
                $event->job->updatePayload($event);
            }
        });

        Event::on(Plugins::class, Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, function(PluginEvent $event) {
            if ($event->plugin === $this) {
                $this->getService()->onBeforeSavePluginSettings($event);
            }
        });
    }

    private function _registerThirdPartyEventListeners(): void
    {
        if (class_exists(SourceNodes::class)) {
            Event::on(SourceNodes::class, SourceNodes::EVENT_REGISTER_SOURCE_NODE_TYPES, function(RegisterSourceNodeTypesEvent $event) {
                if (GqlHelper::canQueryForms()) {
                    $event->types[FormInterface::getName()] = [
                        'node' => 'formieForm',
                        'list' => 'formieForms',
                        'filterArgument' => '',
                        'filterTypeExpression' => '(.+)_Form',
                        'targetInterface' => FormInterface::getName(),
                    ];
                }

                if (GqlHelper::canQuerySubmissions()) {
                    $event->types[SubmissionInterface::getName()] = [
                        'node' => 'formieSubmission',
                        'list' => 'formieSubmissions',
                        'filterArgument' => '',
                        'filterTypeExpression' => '(.+)_Submission',
                        'targetInterface' => SubmissionInterface::getName(),
                    ];
                }
            });
        }

        if (class_exists(FeedMeElements::class)) {
            Event::on(FeedMeElements::class, FeedMeElements::EVENT_REGISTER_FEED_ME_ELEMENTS, function(RegisterFeedMeElementsEvent $e) {
                $e->elements[] = FeedMeSubmission::class;
            });
        }

        if (class_exists(FeedMeFields::class)) {
            Event::on(FeedMeFields::class, FeedMeFields::EVENT_REGISTER_FEED_ME_FIELDS, function(RegisterFeedMeFieldsEvent $e) {
                $fields = Formie::$plugin->getFields()->getRegisteredFormieFields();

                $e->fields = array_merge($e->fields, $fields);
            });
        }
    }

    private function _registerProjectConfigEventListeners(): void
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

        $pdfTemplatesService = $this->getPdfTemplates();
        $projectConfigService
            ->onAdd(PdfTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$pdfTemplatesService, 'handleChangedTemplate'])
            ->onUpdate(PdfTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$pdfTemplatesService, 'handleChangedTemplate'])
            ->onRemove(PdfTemplatesService::CONFIG_TEMPLATES_KEY . '.{uid}', [$pdfTemplatesService, 'handleDeletedTemplate']);

        $integrationsService = $this->getIntegrations();
        $projectConfigService
            ->onAdd(IntegrationsService::CONFIG_INTEGRATIONS_KEY . '.{uid}', [$integrationsService, 'handleChangedIntegration'])
            ->onUpdate(IntegrationsService::CONFIG_INTEGRATIONS_KEY . '.{uid}', [$integrationsService, 'handleChangedIntegration'])
            ->onRemove(IntegrationsService::CONFIG_INTEGRATIONS_KEY . '.{uid}', [$integrationsService, 'handleDeletedIntegration']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['formie'] = ProjectConfigHelper::rebuildProjectConfig();
        });
    }

    private function _registerEmailMessages(): void
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

    private function _registerElementExports(): void
    {
        Event::on(Submission::class, Submission::EVENT_REGISTER_EXPORTERS, function(RegisterElementExportersEvent $e) {
            // Remove defaults, but allow third-party ones
            foreach ($e->exporters as $key => $exporter) {
                if ($exporter === Raw::class) {
                    unset($e->exporters[$key]);
                }

                if ($exporter === Expanded::class) {
                    unset($e->exporters[$key]);
                }
            }

            $e->exporters = array_values($e->exporters);

            $e->exporters[] = SubmissionExport::class;
        });
    }

    private function _registerTemplateRoots(): void
    {
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $e) {
            $e->roots[$this->id] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates/_special';
        });
    }

    private function _registerWidgets(): void
    {
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = RecentSubmissions::class;
        });
    }

    private function _registerResaveCommand(): void
    {
        if (!Craft::$app instanceof ConsoleApplication) {
            return;
        }

        Event::on(ResaveController::class, ConsoleController::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $e) {
            $e->actions['formie-forms'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;
                    
                    return $controller->resaveElements(Form::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Formie forms.',
            ];

            $e->actions['formie-submissions'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    if ($controller->formId !== null) {
                        $formIds = explode(',', $controller->formId);
                    } else {
                        $formIds = Form::find()->ids();
                    }

                    foreach ($formIds as $formId) {
                        $criteria = ['formId' => $formId];

                        $controller->resaveElements(Submission::class, $criteria);
                    }

                    return true;
                },
                'options' => ['formId'],
                'helpSummary' => 'Re-saves Forms submissions.',
                'optionsHelp' => [
                    'formId' => 'The form ID of the submissions to resave.',
                ],
            ];
        });
    }

    private function _registerTemplateHooks(): void
    {
        // Add default captcha integrations
        Craft::$app->getView()->hook('formie.buttons.before', static function(array $context) {
            return Formie::$plugin->getForms()->handleBeforeSubmitHook($context);
        });
    }
}
