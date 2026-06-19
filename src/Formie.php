<?php
namespace verbb\formie;

use verbb\formie\base\PluginTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\SentNotification;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery as DbSubmissionQuery;
use verbb\formie\elements\exporters\FormExport;
use verbb\formie\fields\Forms;
use verbb\formie\fields\Submissions;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\interfaces\PageInterface;
use verbb\formie\gql\interfaces\PageSettingsInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\mutations\ClientFormMutation;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\queries\ClientFormQuery;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\HtmlFormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\helpers\CrossOriginRequestHelper;
use verbb\formie\helpers\Gql as GqlHelper;
use verbb\formie\helpers\ProjectConfigHelper;
use verbb\formie\integrations\feedme\elements\Submission as FeedMeSubmission;
use verbb\formie\integrations\link\FormLinkType;
use verbb\formie\jobs\DebuggableJobInterface;
use verbb\formie\models\Settings;
use verbb\formie\services\CaptchaProviders as CaptchaProvidersService;
use verbb\formie\services\EmailTemplates as EmailTemplatesService;
use verbb\formie\services\FormGroups as FormGroupsService;
use verbb\formie\services\FormTemplates as FormTemplatesService;
use verbb\formie\services\Integrations as IntegrationsService;
use verbb\formie\services\PdfTemplates as PdfTemplatesService;
use verbb\formie\services\Permissions;
use verbb\formie\services\Reports as ReportsService;
use verbb\formie\services\ScheduledReports as ScheduledReportsService;
use verbb\formie\services\SpamProtection as SpamProtectionService;
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
use craft\elements\exporters\Expanded;
use craft\elements\exporters\Raw;
use craft\enums\CmsEdition;
use craft\events\DefineConsoleActionsEvent;
use craft\events\ExecuteGqlQueryEvent;
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
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fields\Link;
use craft\helpers\Console;
use craft\helpers\Cp;
use craft\queue\Queue;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\ElementSources;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\Gql;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Search;
use craft\services\SystemMessages;
use craft\services\UserPermissions;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;

use craft\gatsbyhelper\events\RegisterSourceNodeTypesEvent;
use craft\gatsbyhelper\services\SourceNodes;

use craft\feedme\events\RegisterFeedMeElementsEvent;
use craft\feedme\events\RegisterFeedMeFieldsEvent;
use craft\feedme\services\Elements as FeedMeElements;
use craft\feedme\services\Fields as FeedMeFields;

use yii\base\Event;
use yii\queue\ExecEvent;
use yii\web\Response as WebResponse;

class Formie extends Plugin
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_TWIG_ENVIRONMENT = 'modifyTwigEnvironment';

    
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '4.0.40';
    public string $minVersionRequired = '2.1.5';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;
        
        $this->getCompatibility()->bootstrap();
        
        $this->_registerTwigExtensions();
        $this->_registerFieldTypes();
        $this->_registerVariable();
        $this->_registerElementTypes();
        $this->_registerGarbageCollection();
        $this->_registerGraphQl();
        $this->_registerEventHandlers();
        $this->_registerClientCorsHandler();
        $this->_registerProjectConfigEventHandlers();
        $this->_registerEmailMessages();
        $this->_registerTemplateRoots();
        
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

        if (Craft::$app->getEdition() !== Craft::Solo) {
            $this->_registerPermissions();
        }

        $this->getSpamProtection()->hydrateSettings($this->getSettings());
        $this->getCaptchaProviders()->hydrateLegacyCaptchas($this->getSettings());
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

        if (Craft::$app->getUser()->checkPermission('formie-accessForms')) {
            $nav['subnav']['forms'] = [
                'label' => Craft::t('formie', 'Forms'),
                'url' => 'formie/forms',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('formie-accessSubmissions')) {
            $nav['subnav']['submissions'] = [
                'label' => Craft::t('formie', 'Submissions'),
                'url' => 'formie/submissions',
            ];
        }

        if (Craft::$app->getUser()->checkPermission(Permissions::PERM_ACCESS_REPORTS)) {
            $nav['subnav']['reports'] = [
                'label' => Craft::t('formie', 'Reports'),
                'url' => 'formie/reports',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('formie-accessSentNotifications') && $settings->sentNotifications) {
            $nav['subnav']['sentNotifications'] = [
                'label' => Craft::t('formie', 'Sent Notifications'),
                'url' => 'formie/sent-notifications',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('formie-accessStencils')) {
            $nav['subnav']['stencils'] = [
                'label' => Craft::t('formie', 'Stencils'),
                'url' => 'formie/stencils',
            ];
        }

        if (Craft::$app->getUser()->checkPermission(Permissions::PERM_ACCESS_INTEGRATIONS)) {
            $nav['subnav']['integrations'] = [
                'label' => Craft::t('formie', 'Integrations'),
                'url' => 'formie/integrations/email-marketing',
            ];
        }

        if ($this->getPermissions()->canAccessAnySettings(Craft::$app->getUser()->getIdentity())) {
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

    private function _registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new Extension);
    }

    private function _registerSiteRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            // Legacy route for OAuth callbacks registered before action URLs became the default.
            $event->rules['formie/integrations/callback'] = 'formie/integrations/callback';
            $event->rules['formie/file-upload/upload'] = 'formie/file-upload/upload';
            $event->rules['formie/file-upload/delete'] = 'formie/file-upload/delete';
            $event->rules['formie/file-upload/hydrate'] = 'formie/file-upload/hydrate';
            $event->rules['formie/payment-webhooks/process-webhook'] = 'formie/payment-webhooks/process-webhook';
            $event->rules['formie/payment-webhooks/process-callback'] = 'formie/payment-webhooks/process-callback';
            $event->rules['formie/payment-webhooks/status'] = 'formie/payment-webhooks/status';
            $event->rules['formie/payment-webhooks/poll-status'] = 'formie/payment-webhooks/poll-status';
        });
    }

    private function _registerClientCorsHandler(): void
    {
        Event::on(WebResponse::class, WebResponse::EVENT_BEFORE_SEND, function() {
            $request = Craft::$app->getRequest();

            if (!$request->getIsSiteRequest() || !$request->getIsOptions()) {
                return;
            }

            if (!CrossOriginRequestHelper::isFormieActionPath($request)) {
                return;
            }

            CrossOriginRequestHelper::applyHeaders($request, Craft::$app->getResponse());
        });
    }
    
    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['formie'] = 'formie/base/index';

            $event->rules['formie/forms'] = 'formie/forms/index';
            $event->rules['formie/forms/new'] = 'formie/forms/new';
            $event->rules['formie/forms/edit/<segments:.*>'] = 'formie/forms/edit';
            $event->rules['formie/forms/template-fields-slideout'] = 'formie/forms/template-fields-slideout';
            $event->rules['formie/forms/preview-slideout'] = 'formie/forms/preview-slideout';
            $event->rules['formie/forms/preview-frame'] = 'formie/forms/preview-frame';
            $event->rules['formie/forms/preview-submit'] = 'formie/forms/preview-submit';

            $event->rules['formie/submissions'] = 'formie/submissions/index';
            $event->rules['formie/submissions/<formHandle:{handle}>'] = 'formie/submissions/index';
            $event->rules['formie/submissions/<formHandle:{handle}>/new'] = 'formie/submissions/edit-submission';
            $event->rules['formie/submissions/<formHandle:{handle}>/<submissionId:\d+>'] = 'formie/submissions/edit-submission';

            $event->rules['formie/reports'] = 'formie/reports/index';
            $event->rules['formie/reports/new'] = 'formie/reports/edit';
            $event->rules['formie/reports/create'] = 'formie/reports/create';
            $event->rules['formie/reports/edit/<id:\d+>'] = 'formie/reports/edit';
            $event->rules['formie/reports/view/<id:\d+>'] = 'formie/reports/view';
            $event->rules['formie/reports/view-config/<id:\d+>'] = 'formie/reports/view-config';
            $event->rules['formie/reports/field-columns'] = 'formie/reports/field-columns';
            $event->rules['formie/reports/table-data/<id:\d+>'] = 'formie/reports/table-data';
            $event->rules['formie/reports/viewer-data/<id:\d+>'] = 'formie/reports/viewer-data';
            $event->rules['formie/reports/export/<id:\d+>'] = 'formie/reports/export';
            $event->rules['formie/reports/export-status/<uid:[^/]+>'] = 'formie/reports/export-status';
            $event->rules['formie/reports/download-queued-export/<uid:[^/]+>'] = 'formie/reports/download-queued-export';
            $event->rules['formie/reports/save'] = 'formie/reports/save';
            $event->rules['formie/reports/delete'] = 'formie/reports/delete';
            $event->rules['formie/reports/<reportHandle:{handle}>'] = 'formie/reports/index';

            $event->rules['formie/sent-notifications'] = 'formie/sent-notifications/index';
            $event->rules['formie/sent-notifications/edit/<sentNotificationId:\d+>'] = 'formie/sent-notifications/edit';

            $event->rules['formie/settings'] = 'formie/settings/index';
            $event->rules['formie/settings/general'] = 'formie/settings/index';
            $event->rules['formie/settings/forms'] = 'formie/settings/forms';
            $event->rules['formie/settings/defaults'] = 'formie/settings/defaults';
            $event->rules['formie/settings/fields'] = 'formie/settings/fields';
            $event->rules['formie/settings/submissions'] = 'formie/settings/submissions';
            $event->rules['formie/settings/integrations-settings'] = 'formie/settings/integrations-settings';
            $event->rules['formie/settings/spam'] = 'formie/settings/spam';
            $event->rules['formie/settings/spam-protection'] = 'formie/settings/spam-protection';
            $event->rules['formie/settings/notifications'] = 'formie/notifications/index';
            $event->rules['formie/settings/sent-notifications'] = 'formie/sent-notifications/settings';
            $event->rules['formie/settings/statuses'] = 'formie/statuses/index';
            $event->rules['formie/settings/statuses/new'] = 'formie/statuses/edit';
            $event->rules['formie/settings/statuses/edit/<id:\d+>'] = 'formie/statuses/edit';
            $event->rules['formie/settings/scheduled-reports'] = 'formie/scheduled-reports/index';
            $event->rules['formie/settings/scheduled-reports/new'] = 'formie/scheduled-reports/edit';
            $event->rules['formie/settings/scheduled-reports/edit/<id:\d+>'] = 'formie/scheduled-reports/edit';
            $event->rules['formie/scheduled-reports/save'] = 'formie/scheduled-reports/save';
            $event->rules['formie/scheduled-reports/delete'] = 'formie/scheduled-reports/delete';
            $event->rules['formie/scheduled-reports/test-send'] = 'formie/scheduled-reports/test-send';
            $event->rules['formie/stencils'] = 'formie/stencils/index';
            $event->rules['formie/stencils/new'] = 'formie/stencils/new';
            $event->rules['formie/stencils/edit/<segments:.*>'] = 'formie/stencils/edit';
            $event->rules['formie/settings/stencils'] = 'formie/stencils/index';
            $event->rules['formie/settings/stencils/new'] = 'formie/stencils/new';
            $event->rules['formie/settings/stencils/edit/<segments:.*>'] = 'formie/stencils/edit';
            $event->rules['formie/settings/form-groups'] = 'formie/form-groups/index';
            $event->rules['formie/settings/form-groups/new'] = 'formie/form-groups/edit';
            $event->rules['formie/settings/form-groups/edit/<id:\d+>'] = 'formie/form-groups/edit';
            $event->rules['formie/settings/synced-fields'] = 'formie/synced-fields/index';
            $event->rules['formie/settings/form-templates'] = 'formie/form-templates/index';
            $event->rules['formie/settings/form-templates/new'] = 'formie/form-templates/edit';
            $event->rules['formie/settings/form-templates/edit/<id:\d+>'] = 'formie/form-templates/edit';
            $event->rules['formie/settings/email-templates'] = 'formie/email-templates/index';
            $event->rules['formie/settings/email-templates/new'] = 'formie/email-templates/edit';
            $event->rules['formie/settings/email-templates/edit/<id:\d+>'] = 'formie/email-templates/edit';
            $event->rules['formie/settings/pdf-templates'] = 'formie/pdf-templates/index';
            $event->rules['formie/settings/pdf-templates/new'] = 'formie/pdf-templates/edit';
            $event->rules['formie/settings/pdf-templates/edit/<id:\d+>'] = 'formie/pdf-templates/edit';
            $event->rules['formie/settings/captchas'] = 'formie/integration-settings/captcha-index';

            $event->rules['formie/integrations/address-providers'] = 'formie/integration-settings/address-provider-index';
            $event->rules['formie/integrations/address-providers/new'] = 'formie/integration-settings/edit-address-provider';
            $event->rules['formie/integrations/address-providers/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-address-provider';
            $event->rules['formie/integrations/elements'] = 'formie/integration-settings/element-index';
            $event->rules['formie/integrations/elements/new'] = 'formie/integration-settings/edit-element';
            $event->rules['formie/integrations/elements/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-element';
            $event->rules['formie/integrations/email-marketing'] = 'formie/integration-settings/email-marketing-index';
            $event->rules['formie/integrations/email-marketing/new'] = 'formie/integration-settings/edit-email-marketing';
            $event->rules['formie/integrations/email-marketing/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-email-marketing';
            $event->rules['formie/integrations/crm'] = 'formie/integration-settings/crm-index';
            $event->rules['formie/integrations/crm/new'] = 'formie/integration-settings/edit-crm';
            $event->rules['formie/integrations/crm/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-crm';
            $event->rules['formie/integrations/help-desk'] = 'formie/integration-settings/help-desk-index';
            $event->rules['formie/integrations/help-desk/new'] = 'formie/integration-settings/edit-help-desk';
            $event->rules['formie/integrations/help-desk/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-help-desk';
            $event->rules['formie/integrations/messaging'] = 'formie/integration-settings/messaging-index';
            $event->rules['formie/integrations/messaging/new'] = 'formie/integration-settings/edit-messaging';
            $event->rules['formie/integrations/messaging/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-messaging';
            $event->rules['formie/integrations/payments'] = 'formie/integration-settings/payment-index';
            $event->rules['formie/integrations/payments/new'] = 'formie/integration-settings/edit-payment';
            $event->rules['formie/integrations/payments/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-payment';
            $event->rules['formie/integrations/automations'] = 'formie/integration-settings/automation-index';
            $event->rules['formie/integrations/automations/new'] = 'formie/integration-settings/edit-automation';
            $event->rules['formie/integrations/automations/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-automation';
            $event->rules['formie/integrations/miscellaneous'] = 'formie/integration-settings/miscellaneous-index';
            $event->rules['formie/integrations/miscellaneous/new'] = 'formie/integration-settings/edit-miscellaneous';
            $event->rules['formie/integrations/miscellaneous/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-miscellaneous';

            $event->rules['formie/settings/address-providers'] = 'formie/integration-settings/address-provider-index';
            $event->rules['formie/settings/address-providers/new'] = 'formie/integration-settings/edit-address-provider';
            $event->rules['formie/settings/address-providers/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-address-provider';
            $event->rules['formie/settings/elements'] = 'formie/integration-settings/element-index';
            $event->rules['formie/settings/elements/new'] = 'formie/integration-settings/edit-element';
            $event->rules['formie/settings/elements/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-element';
            $event->rules['formie/settings/email-marketing'] = 'formie/integration-settings/email-marketing-index';
            $event->rules['formie/settings/email-marketing/new'] = 'formie/integration-settings/edit-email-marketing';
            $event->rules['formie/settings/email-marketing/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-email-marketing';
            $event->rules['formie/settings/crm'] = 'formie/integration-settings/crm-index';
            $event->rules['formie/settings/crm/new'] = 'formie/integration-settings/edit-crm';
            $event->rules['formie/settings/crm/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-crm';
            $event->rules['formie/settings/help-desk'] = 'formie/integration-settings/help-desk-index';
            $event->rules['formie/settings/help-desk/new'] = 'formie/integration-settings/edit-help-desk';
            $event->rules['formie/settings/help-desk/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-help-desk';
            $event->rules['formie/settings/messaging'] = 'formie/integration-settings/messaging-index';
            $event->rules['formie/settings/messaging/new'] = 'formie/integration-settings/edit-messaging';
            $event->rules['formie/settings/messaging/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-messaging';
            $event->rules['formie/settings/payments'] = 'formie/integration-settings/payment-index';
            $event->rules['formie/settings/payments/new'] = 'formie/integration-settings/edit-payment';
            $event->rules['formie/settings/payments/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-payment';
            $event->rules['formie/settings/automations'] = 'formie/integration-settings/automation-index';
            $event->rules['formie/settings/automations/new'] = 'formie/integration-settings/edit-automation';
            $event->rules['formie/settings/automations/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-automation';
            $event->rules['formie/settings/miscellaneous'] = 'formie/integration-settings/miscellaneous-index';
            $event->rules['formie/settings/miscellaneous/new'] = 'formie/integration-settings/edit-miscellaneous';
            $event->rules['formie/settings/miscellaneous/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-miscellaneous';
            $event->rules['formie/settings/support'] = 'formie/support/index';
            $event->rules['formie/settings/import-export'] = 'formie/import-export/index';
            $event->rules['formie/settings/import-export/import-configure/<filename:.*>'] = 'formie/import-export/import-configure';
            $event->rules['formie/settings/import-export/import-completed/<formId:\d+>'] = 'formie/import-export/import-completed';
        });
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $permissions = $this->getPermissions();

            $event->permissions[] = [
                'heading' => Craft::t('formie', 'Formie'),
                'permissions' => [
                    Permissions::PERM_ACCESS_FORMS => [
                        'label' => Craft::t('formie', 'Access forms'),
                        'nested' => $permissions->getFormPermissionDefinitions(),
                    ],
                    Permissions::PERM_ACCESS_SUBMISSIONS => [
                        'label' => Craft::t('formie', 'Access submissions'),
                        'nested' => $permissions->getSubmissionPermissionDefinitions(),
                    ],
                    Permissions::PERM_ACCESS_REPORTS => [
                        'label' => Craft::t('formie', 'Access reports'),
                        'nested' => $permissions->getReportPermissionDefinitions(),
                    ],
                    'formie-accessSentNotifications' => [
                        'label' => Craft::t('formie', 'Access sent notifications'),
                        'nested' => $permissions->getSentNotificationPermissionDefinitions(),
                    ],
                    'formie-accessStencils' => ['label' => Craft::t('formie', 'Access stencils')],
                    Permissions::PERM_ACCESS_INTEGRATIONS => ['label' => Craft::t('formie', 'Access integrations')],
                    Permissions::PERM_ACCESS_SETTINGS => [
                        'label' => Craft::t('formie', 'Access settings'),
                        'nested' => $permissions->getSettingsPermissionDefinitions(),
                    ],
                ],
            ];
        });
    }

    private function _registerVariable(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('formie', FormieVariable::class);
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
            // Delete incomplete submissions older than the configured interval.
            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout('    > purging incomplete Formie submissions ... ');
            }

            $this->getSubmissions()->pruneIncompleteSubmissions();

            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout("done\n", Console::FG_GREEN);
                Console::stdout('    > purging Formie submissions based on data retention ... ');
            }

            // Deletes submissions if they are past the form data retention settings.
            $this->getSubmissions()->pruneDataRetentionSubmissions();

            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout("done\n", Console::FG_GREEN);
                Console::stdout('    > purging Formie sent notifications ... ');
            }

            // Delete sent notifications older than the configured interval.
            $this->getSentNotifications()->pruneSentNotifications();

            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout("done\n", Console::FG_GREEN);
                Console::stdout('    > purging expired File Upload field assets ... ');
            }

            $this->getFileUploads()->pruneExpiredFieldAssets(
                Craft::$app instanceof ConsoleApplication ? Craft::$app : null,
            );

            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout("done\n", Console::FG_GREEN);
                Console::stdout('    > purging stale pending File Upload assets ... ');
            }

            $this->getFileUploads()->purgeStalePendingUploads();

            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout("done\n", Console::FG_GREEN);
                Console::stdout('    > purging expired Formie report exports ... ');
            }

            $this->getReportExportFiles()->pruneExpired();

            if (Craft::$app instanceof ConsoleApplication) {
                Console::stdout("done\n", Console::FG_GREEN);
            }
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
                HtmlFormQuery::getQueries(),
                ClientFormQuery::getQueries(),
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
                ClientFormMutation::getMutations(),
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

            $forms = $this->getForms()->getAllForms();

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

        // Strip GraphQL validation "Did you mean" hints when devMode is off (Craft passes the same flag
        // into Gql::executeQuery() for validation rule selection; some rules still leak suggestions).
        Event::on(Gql::class, Gql::EVENT_AFTER_EXECUTE_GQL_QUERY, function(ExecuteGqlQueryEvent $event): void {
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                return;
            }

            if (empty($event->result['errors']) || !is_array($event->result['errors'])) {
                return;
            }

            foreach ($event->result['errors'] as $i => $error) {
                if (!is_array($error)) {
                    continue;
                }

                $msg = $error['message'] ?? null;
                if (!is_string($msg) || $msg === '') {
                    continue;
                }

                $sanitized = GqlHelper::stripGraphqlSuggestionHints($msg);
                if ($sanitized !== '') {
                    $event->result['errors'][$i]['message'] = $sanitized;
                }
            }
        });
    }

    private function _registerEventHandlers(): void
    {
        Event::on(Form::class, Form::EVENT_AFTER_SAVE, function() {
            $this->getForms()->invalidateFormCaches();
            DbSubmissionQuery::invalidateStaticCaches();
        });

        Event::on(Form::class, Form::EVENT_AFTER_DELETE, function() {
            $this->getForms()->invalidateFormCaches();
            DbSubmissionQuery::invalidateStaticCaches();
        });
        
        Event::on(Form::class, Form::EVENT_AFTER_RESTORE, function() {
            $this->getForms()->invalidateFormCaches();
            DbSubmissionQuery::invalidateStaticCaches();
        });

        Event::on(UsersController::class, UsersController::EVENT_DEFINE_CONTENT_SUMMARY, [$this->getSubmissions(), 'defineUserSubmissions']);
        Event::on(UserElement::class, UserElement::EVENT_AFTER_DELETE, [$this->getSubmissions(), 'deleteUserSubmissions']);
        Event::on(UserElement::class, UserElement::EVENT_AFTER_RESTORE, [$this->getSubmissions(), 'restoreUserSubmissions']);
        Event::on(ElementSources::class, ElementSources::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES, [$this->getSubmissions(), 'defineSourceTableAttributes']);
        Event::on(ElementSources::class, ElementSources::EVENT_DEFINE_SOURCE_SORT_OPTIONS, [$this->getSubmissions(), 'defineSourceSortOptions']);

        // Custom handling of element chips to prevent CP slide-out editing which isn't supported
        Event::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CHIP_HTML, [Form::class, 'defineElementChipHtml']);
        Event::on(Cp::class, Cp::EVENT_DEFINE_ELEMENT_CHIP_HTML, [Submission::class, 'defineElementChipHtml']);

        // Fix lack of support for submission-specific fields to index
        Event::on(Search::class, Search::EVENT_BEFORE_INDEX_KEYWORDS, [$this->getSubmissions(), 'beforeIndexKeywords']);

        // Add additional error information to queue jobs when there's an error
        Event::on(Queue::class, Queue::EVENT_AFTER_ERROR, function(ExecEvent $event) {
            if ($event->error && $event->job instanceof DebuggableJobInterface) {
                $event->job->onError($event);
            }
        });

        Event::on(Plugins::class, Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, function(PluginEvent $event) {
            if ($event->plugin === $this) {
                $this->getService()->onBeforeSavePluginSettings($event);
            }
        });

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
            Event::on(FeedMeElements::class, FeedMeElements::EVENT_REGISTER_FEED_ME_ELEMENTS, function(RegisterFeedMeElementsEvent $event) {
                $event->elements[] = FeedMeSubmission::class;
            });
        }

        if (class_exists(FeedMeFields::class)) {
            Event::on(FeedMeFields::class, FeedMeFields::EVENT_REGISTER_FEED_ME_FIELDS, function(RegisterFeedMeFieldsEvent $event) {
                $fields = Formie::$plugin->getFields()->getRegisteredFormieFields();

                $event->fields = array_merge($event->fields, $fields);
            });
        }

        if (version_compare(Craft::$app->getInfo()->version, '5.3.0', '>=')) {
            Event::on(Link::class, Link::EVENT_REGISTER_LINK_TYPES, function(RegisterComponentTypesEvent $event) {
                $event->types[] = FormLinkType::class;
            });
        }
    }

    private function _registerProjectConfigEventHandlers(): void
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

        $formGroupsService = $this->getFormGroups();
        $projectConfigService
            ->onAdd(FormGroupsService::CONFIG_GROUPS_KEY . '.{uid}', [$formGroupsService, 'handleChangedGroup'])
            ->onUpdate(FormGroupsService::CONFIG_GROUPS_KEY . '.{uid}', [$formGroupsService, 'handleChangedGroup'])
            ->onRemove(FormGroupsService::CONFIG_GROUPS_KEY . '.{uid}', [$formGroupsService, 'handleDeletedGroup']);

        $reportsService = $this->getReports();
        $projectConfigService
            ->onAdd(ReportsService::CONFIG_REPORTS_KEY . '.{uid}', [$reportsService, 'handleChangedReport'])
            ->onUpdate(ReportsService::CONFIG_REPORTS_KEY . '.{uid}', [$reportsService, 'handleChangedReport'])
            ->onRemove(ReportsService::CONFIG_REPORTS_KEY . '.{uid}', [$reportsService, 'handleDeletedReport']);

        $scheduledReportsService = $this->getScheduledReports();
        $projectConfigService
            ->onAdd(ScheduledReportsService::CONFIG_SCHEDULED_REPORTS_KEY . '.{uid}', [$scheduledReportsService, 'handleChangedScheduledReport'])
            ->onUpdate(ScheduledReportsService::CONFIG_SCHEDULED_REPORTS_KEY . '.{uid}', [$scheduledReportsService, 'handleChangedScheduledReport'])
            ->onRemove(ScheduledReportsService::CONFIG_SCHEDULED_REPORTS_KEY . '.{uid}', [$scheduledReportsService, 'handleDeletedScheduledReport']);

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

        $captchaProvidersService = $this->getCaptchaProviders();
        $projectConfigService
            ->onAdd(CaptchaProvidersService::CONFIG_CAPTCHA_PROVIDERS_KEY . '.{handle}', [$captchaProvidersService, 'handleChangedProvider'])
            ->onUpdate(CaptchaProvidersService::CONFIG_CAPTCHA_PROVIDERS_KEY . '.{handle}', [$captchaProvidersService, 'handleChangedProvider'])
            ->onRemove(CaptchaProvidersService::CONFIG_CAPTCHA_PROVIDERS_KEY . '.{handle}', [$captchaProvidersService, 'handleDeletedProvider']);

        $spamProtectionService = $this->getSpamProtection();
        $projectConfigService
            ->onAdd(SpamProtectionService::CONFIG_SPAM_SETTINGS_KEY, [$spamProtectionService, 'handleChangedSettings'])
            ->onUpdate(SpamProtectionService::CONFIG_SPAM_SETTINGS_KEY, [$spamProtectionService, 'handleChangedSettings'])
            ->onRemove(SpamProtectionService::CONFIG_SPAM_SETTINGS_KEY, [$spamProtectionService, 'handleDeletedSettings']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['formie'] = ProjectConfigHelper::rebuildProjectConfig();
        });
    }

    private function _registerEmailMessages(): void
    {
        Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
            $event->messages[] = [
                'key' => 'formie_failed_notification',
                'heading' => Craft::t('formie', 'formie_failed_notification_heading'),
                'subject' => Craft::t('formie', 'formie_failed_notification_subject'),
                'body' => Craft::t('formie', 'formie_failed_notification_body'),
            ];
            $event->messages[] = [
                'key' => 'formie_failed_integration',
                'heading' => Craft::t('formie', 'formie_failed_integration_heading'),
                'subject' => Craft::t('formie', 'formie_failed_integration_subject'),
                'body' => Craft::t('formie', 'formie_failed_integration_body'),
            ];
        });
    }

    private function _registerElementExports(): void
    {
        Event::on(Submission::class, Submission::EVENT_REGISTER_EXPORTERS, function(RegisterElementExportersEvent $event) {
            // Submissions export via reports; remove Craft defaults but allow third-party exporters.
            foreach ($event->exporters as $key => $exporter) {
                if ($exporter === Raw::class || $exporter === Expanded::class) {
                    unset($event->exporters[$key]);
                }
            }

            $event->exporters = array_values($event->exporters);
        });

        Event::on(Form::class, Form::EVENT_REGISTER_EXPORTERS, function(RegisterElementExportersEvent $event) {
            // Remove defaults, but allow third-party ones
            foreach ($event->exporters as $key => $exporter) {
                if ($exporter === Raw::class) {
                    unset($event->exporters[$key]);
                }

                if ($exporter === Expanded::class) {
                    unset($event->exporters[$key]);
                }
            }

            $event->exporters = array_values($event->exporters);

            $event->exporters[] = FormExport::class;
        });
    }

    private function _registerTemplateRoots(): void
    {
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots[$this->id] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates/_special';
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

        Event::on(ResaveController::class, ConsoleController::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
            $event->actions['formie-forms'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;
                    
                    return $controller->resaveElements(Form::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Formie forms.',
            ];

            $event->actions['formie-submissions'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    if ($controller->formId !== null) {
                        $formIds = explode(',', $controller->formId);
                    } else {
                        $formIds = Form::find()->ids();
                    }

                    foreach ($formIds as $formId) {
                        $criteria = ['formId' => $formId, 'updateTitle' => $controller->updateTitle];

                        $controller->resaveElements(Submission::class, $criteria);
                    }

                    return true;
                },
                'options' => ['formId', 'updateTitle'],
                'helpSummary' => 'Re-saves Forms submissions.',
                'optionsHelp' => [
                    'formId' => 'The form ID of the submissions to resave.',
                    'updateTitle' => 'Whether to force the title‘s to update according to the Submission Title Format.',
                ],
            ];
        });
    }

}
