<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\cache\RenderCache;
use verbb\formie\client\bootstrap\FormBootstrapBuilder;
use verbb\formie\client\bootstrap\FormDefinitionBuilder;
use verbb\formie\client\modules\ClientModuleManifestBuilder;
use verbb\formie\client\ClientSessionService;
use verbb\formie\deprecations\PluginDeprecations;
use verbb\formie\elements\Submission as SubmissionElement;
use verbb\formie\events\ModifyTwigEnvironmentEvent;
use verbb\formie\server\ServerRenderPayloadBuilder;
use verbb\formie\services\Countries;
use verbb\formie\services\Compatibility;
use verbb\formie\services\CustomFields;
use verbb\formie\services\EmailDomains;
use verbb\formie\services\Emails;
use verbb\formie\services\EmailTemplates;
use verbb\formie\services\Factories;
use verbb\formie\services\FieldTypeDefinitions;
use verbb\formie\services\FieldPalette;
use verbb\formie\services\Fields;
use verbb\formie\services\FileUploads;
use verbb\formie\services\FormDefaults;
use verbb\formie\services\Forms;
use verbb\formie\services\FormGroups;
use verbb\formie\services\FormPreview;
use verbb\formie\services\FormTemplates;
use verbb\formie\services\Integrations;
use verbb\formie\services\IntegrationDispatch;
use verbb\formie\services\IntegrationExecutor;
use verbb\formie\services\IntegrationTriggers;
use verbb\formie\services\NotificationTriggers;
use verbb\formie\services\Notifications;
use verbb\formie\services\Payments;
use verbb\formie\services\PdfTemplates;
use verbb\formie\services\Phone;
use verbb\formie\services\Plans;
use verbb\formie\services\OptionSources;
use verbb\formie\services\Repair;
use verbb\formie\services\Relations;
use verbb\formie\services\Rendering;
use verbb\formie\services\FrontendAssets;
use verbb\formie\services\SentNotifications;
use verbb\formie\services\Service;
use verbb\formie\services\Statuses;
use verbb\formie\services\Stencils;
use verbb\formie\services\Submissions;
use verbb\formie\services\SubmissionProcessor;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\services\Subscriptions;
use verbb\formie\services\StorageManager;
use verbb\formie\services\ThemeConfig;
use verbb\formie\services\WorkflowTaskRunner;
use verbb\formie\theme\slots\FieldSlotRegistry;
use verbb\formie\theme\slots\FormSlotRegistry;
use verbb\formie\web\assets\cp\CpReactAsset;

use Craft;
use craft\elements\User;
use craft\helpers\App;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;
use verbb\base\services\Templates;
use verbb\formie\helpers\Plugin as FormiePluginHelper;
use verbb\formie\models\HiddenDefaultTemplateContext;
use verbb\formie\models\HiddenDefaultTemplateFormContext;
use verbb\formie\models\HiddenDefaultTemplateRequestContext;
use verbb\formie\models\HiddenDefaultTemplateSiteContext;

use nystudio107\pluginvite\services\VitePluginService;

use ArrayAccess;

use yii\base\Event;
use yii\base\Model;
use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Formie $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    use PluginDeprecations;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('formie');

        // Build the Twig allow-list before the templates service is registered
        // so extension code can widen the safe client surface without replacing
        // Formie's sandbox configuration wholesale.
        $event = new ModifyTwigEnvironmentEvent([
            'allowedTags' => [],
            'allowedFilters' => [],
            'allowedFunctions' => [],
            'allowedMethods' => [],
            'allowedProperties' => [],
        ]);

        $event->allowedMethods[FieldValueInterface::class] = ['__toString'];

        $event->allowedProperties[FieldValueInterface::class] = function(FieldValueInterface $value, string $property): bool {
            if ($value instanceof Model && in_array($property, $value->attributes(), true)) {
                return true;
            }

            if ($value instanceof ArrayAccess && $value->offsetExists($property)) {
                return true;
            }

            return property_exists($value, $property);
        };

        $event->allowedProperties[SubmissionElement::class] = function(SubmissionElement $submission, string $property): bool {
            if (in_array($property, $submission->attributes(), true)) {
                return true;
            }

            if (strncmp($property, 'field:', 6) === 0) {
                return $submission->getFieldByHandle(substr($property, 6)) !== null;
            }

            return $submission->getFieldByHandle($property) !== null;
        };

        $allowModelAttributes = function(Model $model, string $property): bool {
            return in_array($property, $model->attributes(), true);
        };

        $event->allowedProperties[HiddenDefaultTemplateContext::class] = $allowModelAttributes;
        $event->allowedProperties[HiddenDefaultTemplateFormContext::class] = $allowModelAttributes;
        $event->allowedProperties[HiddenDefaultTemplateSiteContext::class] = $allowModelAttributes;
        $event->allowedProperties[HiddenDefaultTemplateRequestContext::class] = $allowModelAttributes;

        $event->allowedProperties[User::class] = function(User $user, string $property): bool {
            if (in_array($property, ['id', 'email', 'username', 'fullName', 'firstName', 'lastName'], true)) {
                return true;
            }

            return $user->getFieldLayout()?->getFieldByHandle($property) !== null;
        };

        Event::trigger(self::class, self::EVENT_MODIFY_TWIG_ENVIRONMENT, $event);

        return [
            'components' => [
                'countries' => Countries::class,
                'compatibility' => Compatibility::class,
                'customFields' => CustomFields::class,
                'cpAssets' => [
                    'class' => VitePluginService::class,
                    'assetClass' => CpReactAsset::class,
                    'useDevServer' => true,
                    'devServerPublic' => rtrim(App::parseEnv('$FORMIE_CP_DEV_SERVER_PUBLIC') ?: 'http://localhost:3900/', '/') . '/',
                    'errorEntry' => 'js/main.js',
                    'cacheKeySuffix' => '',
                    'devServerInternal' => rtrim(App::parseEnv('$FORMIE_CP_DEV_SERVER_INTERNAL') ?: 'http://localhost:3900/', '/') . '/',
                    'checkDevServer' => true,
                    'includeReactRefreshShim' => true,
                ],
                'emailDomains' => EmailDomains::class,
                'emails' => Emails::class,
                'emailTemplates' => EmailTemplates::class,
                'factories' => Factories::class,
                'fieldTypeDefinitions' => FieldTypeDefinitions::class,
                'fieldPalette' => FieldPalette::class,
                'fields' => Fields::class,
                'fieldSlotRegistry' => FieldSlotRegistry::class,
                'fileUploads' => FileUploads::class,
                'formDefaults' => FormDefaults::class,
                'forms' => Forms::class,
                'formSlotRegistry' => FormSlotRegistry::class,
                'clientFormBootstrapBuilder' => FormBootstrapBuilder::class,
                'clientFormDefinitionBuilder' => FormDefinitionBuilder::class,
                'formGroups' => FormGroups::class,
                'formPreview' => FormPreview::class,
                'formTemplates' => FormTemplates::class,
                'integrations' => Integrations::class,
                'integrationDispatch' => IntegrationDispatch::class,
                'integrationExecutor' => IntegrationExecutor::class,
                'integrationTriggers' => IntegrationTriggers::class,
                'notificationTriggers' => NotificationTriggers::class,
                'serverRenderPayloadBuilder' => ServerRenderPayloadBuilder::class,
                'clientModuleManifestBuilder' => ClientModuleManifestBuilder::class,
                'notifications' => Notifications::class,
                'payments' => Payments::class,
                'pdfTemplates' => PdfTemplates::class,
                'phone' => Phone::class,
                'plans' => Plans::class,
                'optionSources' => OptionSources::class,
                'repair' => Repair::class,
                'relations' => Relations::class,
                'renderCache' => RenderCache::class,
                'rendering' => Rendering::class,
                'frontendAssets' => FrontendAssets::class,
                'clientSessionService' => ClientSessionService::class,
                'sentNotifications' => SentNotifications::class,
                'service' => Service::class,
                'statuses' => Statuses::class,
                'stencils' => Stencils::class,
                'storageManager' => StorageManager::class,
                'submissions' => Submissions::class,
                'submissionProcessor' => SubmissionProcessor::class,
                'submissionDrafts' => SubmissionDrafts::class,
                'submissionWorkflow' => SubmissionWorkflow::class,
                'subscriptions' => Subscriptions::class,
                'templates' => [
                    'class' => Templates::class,
                    'pluginClass' => Formie::class,
                    'allowedTags' => $event->allowedTags,
                    'allowedFilters' => $event->allowedFilters,
                    'allowedFunctions' => $event->allowedFunctions,
                    'allowedMethods' => $event->allowedMethods,
                    'allowedProperties' => $event->allowedProperties,
                ],
                'themeConfig' => ThemeConfig::class,
                'workflowTaskRunner' => WorkflowTaskRunner::class,
            ],
        ];
    }


    // Public Methods
    // =========================================================================

    public function __construct($id, $parent = null, array $config = [])
    {
        // Set the source language to be the current site, not `en-US`. We could have a German site (primary) where all field content
        // is written in German. Then an English site we translate to. The German site will then show all texts in English as the source
        // and destination message is the same, because it will translate to the `sourceLanguage` - `en-US`.
        // This isn't an issue for other plugins, but we use the `formie` translation category to translate user-created content
        // not just plugin-created content, which is always going to be written in English.
        // Also, only do this for the front-end, so that the users' language preference is respected in the CP
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            if ($currentSite = Craft::$app->getSites()->getCurrentSite()) {
                $config['sourceLanguage'] = $currentSite->language;
            }
        }

        return parent::__construct($id, $parent , $config);
    }

    public function getCountries(): Countries
    {
        return $this->get('countries');
    }

    public function getCompatibility(): Compatibility
    {
        return $this->get('compatibility');
    }

    public function getCustomFields(): CustomFields
    {
        return $this->get('customFields');
    }

    public function getCpAssets(): VitePluginService
    {
        return $this->get('cpAssets');
    }

    public function registerCpFormBuilderAssets(): void
    {
        FormiePluginHelper::registerCpFormBuilderAssets();
    }

    public function registerCpNewFormAssets(): void
    {
        FormiePluginHelper::registerCpNewFormAssets();
    }

    public function registerCpDefaultsAssets(): void
    {
        FormiePluginHelper::registerCpDefaultsAssets();
    }

    public function registerCpFieldPaletteAssets(): void
    {
        FormiePluginHelper::registerCpFieldPaletteAssets();
    }

    public function registerCpStencilNewAssets(): void
    {
        FormiePluginHelper::registerCpStencilNewAssets();
    }

    public function registerCpStencilEditAssets(): void
    {
        FormiePluginHelper::registerCpStencilEditAssets();
    }

    public function registerCpIntegrationConnectAssets(): void
    {
        FormiePluginHelper::registerCpIntegrationConnectAssets();
    }

    public function registerCpPluginSettingsAssets(): void
    {
        FormiePluginHelper::registerCpPluginSettingsAssets();
    }

    public function registerCpSubmissionsAssets(): void
    {
        FormiePluginHelper::registerCpSubmissionsAssets();
    }

    public function registerCpSentNotificationsAssets(): void
    {
        FormiePluginHelper::registerCpSentNotificationsAssets();
    }

    public function registerCpWidgetsAssets(): void
    {
        FormiePluginHelper::registerCpWidgetsAssets();
    }

    public function getEmailDomains(): EmailDomains
    {
        return $this->get('emailDomains');
    }

    public function getEmails(): Emails
    {
        return $this->get('emails');
    }

    public function getEmailTemplates(): EmailTemplates
    {
        return $this->get('emailTemplates');
    }

    public function getFactories(): Factories
    {
        return $this->get('factories');
    }

    public function getFields(): Fields
    {
        return $this->get('fields');
    }

    public function getFieldPalette(): FieldPalette
    {
        return $this->get('fieldPalette');
    }

    public function getFrontendAssets(): FrontendAssets
    {
        return $this->get('frontendAssets');
    }

    public function getClientFormBootstrapBuilder(): FormBootstrapBuilder
    {
        return $this->get('clientFormBootstrapBuilder');
    }

    public function getClientFormDefinitionBuilder(): FormDefinitionBuilder
    {
        return $this->get('clientFormDefinitionBuilder');
    }

    public function getServerRenderPayloadBuilder(): ServerRenderPayloadBuilder
    {
        return $this->get('serverRenderPayloadBuilder');
    }

    public function getClientModuleManifestBuilder(): ClientModuleManifestBuilder
    {
        return $this->get('clientModuleManifestBuilder');
    }

    public function getClientSessionService(): ClientSessionService
    {
        return $this->get('clientSessionService');
    }

    public function getFieldTypeDefinitions(): FieldTypeDefinitions
    {
        return $this->get('fieldTypeDefinitions');
    }

    public function getForms(): Forms
    {
        return $this->get('forms');
    }

    public function getFormDefaults(): FormDefaults
    {
        return $this->get('formDefaults');
    }

    public function getFormGroups(): FormGroups
    {
        return $this->get('formGroups');
    }

    public function getFormPreview(): FormPreview
    {
        return $this->get('formPreview');
    }

    public function getFormTemplates(): FormTemplates
    {
        return $this->get('formTemplates');
    }

    public function getIntegrations(): Integrations
    {
        return $this->get('integrations');
    }

    public function getIntegrationDispatch(): IntegrationDispatch
    {
        return $this->get('integrationDispatch');
    }

    public function getIntegrationExecutor(): IntegrationExecutor
    {
        return $this->get('integrationExecutor');
    }

    public function getIntegrationTriggers(): IntegrationTriggers
    {
        return $this->get('integrationTriggers');
    }

    public function getNotificationTriggers(): NotificationTriggers
    {
        return $this->get('notificationTriggers');
    }

    public function getNotifications(): Notifications
    {
        return $this->get('notifications');
    }

    public function getPayments(): Payments
    {
        return $this->get('payments');
    }

    public function getPdfTemplates(): PdfTemplates
    {
        return $this->get('pdfTemplates');
    }

    public function getPhone(): Phone
    {
        return $this->get('phone');
    }

    public function getPlans(): Plans
    {
        return $this->get('plans');
    }

    public function getOptionSources(): OptionSources
    {
        return $this->get('optionSources');
    }

    public function getRepair(): Repair
    {
        return $this->get('repair');
    }

    public function getRelations(): Relations
    {
        return $this->get('relations');
    }

    public function getRenderCache(): RenderCache
    {
        return $this->get('renderCache');
    }

    public function getRendering(): Rendering
    {
        return $this->get('rendering');
    }

    public function getSentNotifications(): SentNotifications
    {
        return $this->get('sentNotifications');
    }

    public function getService(): Service
    {
        return $this->get('service');
    }

    public function getStatuses(): Statuses
    {
        return $this->get('statuses');
    }

    public function getStencils(): Stencils
    {
        return $this->get('stencils');
    }

    public function getSubmissions(): Submissions
    {
        return $this->get('submissions');
    }

    public function getSubmissionProcessor(): SubmissionProcessor
    {
        return $this->get('submissionProcessor');
    }

    public function getSubmissionWorkflow(): SubmissionWorkflow
    {
        return $this->get('submissionWorkflow');
    }

    public function getWorkflowTaskRunner(): WorkflowTaskRunner
    {
        return $this->get('workflowTaskRunner');
    }

    public function getSubmissionDrafts(): SubmissionDrafts
    {
        return $this->get('submissionDrafts');
    }

    public function getStorageManager(): StorageManager
    {
        return $this->get('storageManager');
    }

    public function getThemeConfigService(): ThemeConfig
    {
        return $this->get('themeConfig');
    }

    public function getFormSlotRegistry(): FormSlotRegistry
    {
        return $this->get('formSlotRegistry');
    }

    public function getFieldSlotRegistry(): FieldSlotRegistry
    {
        return $this->get('fieldSlotRegistry');
    }

    public function getSubscriptions(): Subscriptions
    {
        return $this->get('subscriptions');
    }

    public function getFileUploads(): FileUploads
    {
        return $this->get('fileUploads');
    }

    public function getTemplates(): Templates
    {
        return $this->get('templates');
    }
}
