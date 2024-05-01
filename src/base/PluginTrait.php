<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\events\ModifyTwigEnvironmentEvent;
use verbb\formie\services\Emails;
use verbb\formie\services\EmailTemplates;
use verbb\formie\services\Fields;
use verbb\formie\services\Forms;
use verbb\formie\services\FormTemplates;
use verbb\formie\services\Integrations;
use verbb\formie\services\NestedFields;
use verbb\formie\services\Notifications;
use verbb\formie\services\Payments;
use verbb\formie\services\PdfTemplates;
use verbb\formie\services\Phone;
use verbb\formie\services\Plans;
use verbb\formie\services\PredefinedOptions;
use verbb\formie\services\Relations;
use verbb\formie\services\RenderCache;
use verbb\formie\services\Rendering;
use verbb\formie\services\SentNotifications;
use verbb\formie\services\Service;
use verbb\formie\services\Statuses;
use verbb\formie\services\Stencils;
use verbb\formie\services\Submissions;
use verbb\formie\services\Subscriptions;
use verbb\formie\services\Syncs;
use verbb\formie\services\Tokens;
use verbb\formie\web\assets\forms\FormsAsset;
use verbb\base\BaseHelper;
use verbb\base\services\Templates;

use Craft;

use yii\base\Event;
use yii\log\Logger;

use nystudio107\pluginvite\services\VitePluginService;

trait PluginTrait
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_TWIG_ENVIRONMENT = 'modifyTwigEnvironment';


    // Properties
    // =========================================================================

    /**
     * @var Formie
     */
    public static Formie $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('formie', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'formie');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('formie', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'formie');
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

    public function getEmails(): Emails
    {
        return $this->get('emails');
    }

    public function getEmailTemplates(): EmailTemplates
    {
        return $this->get('emailTemplates');
    }

    public function getFields(): Fields
    {
        return $this->get('fields');
    }

    public function getForms(): Forms
    {
        return $this->get('forms');
    }

    public function getFormTemplates(): FormTemplates
    {
        return $this->get('formTemplates');
    }

    public function getIntegrations(): Integrations
    {
        return $this->get('integrations');
    }

    public function getNestedFields(): NestedFields
    {
        return $this->get('nestedFields');
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

    public function getPredefinedOptions(): PredefinedOptions
    {
        return $this->get('predefinedOptions');
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

    public function getSubscriptions(): Subscriptions
    {
        return $this->get('subscriptions');
    }

    public function getSyncs(): Syncs
    {
        return $this->get('syncs');
    }

    public function getTemplates(): Templates
    {
        return $this->get('templates');
    }

    public function getTokens(): Tokens
    {
        return $this->get('tokens');
    }

    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        // Allow plugins to modify the template variables
        $event = new ModifyTwigEnvironmentEvent([
            'allowedTags' => [],
            'allowedFilters' => [],
            'allowedFunctions' => [],
            'allowedMethods' => [],
            'allowedProperties' => [],
        ]);
        Event::trigger(self::class, self::EVENT_MODIFY_TWIG_ENVIRONMENT, $event);

        $this->setComponents([
            'emails' => Emails::class,
            'emailTemplates' => EmailTemplates::class,
            'fields' => Fields::class,
            'forms' => Forms::class,
            'formTemplates' => FormTemplates::class,
            'integrations' => Integrations::class,
            'nestedFields' => NestedFields::class,
            'notifications' => Notifications::class,
            'payments' => Payments::class,
            'pdfTemplates' => PdfTemplates::class,
            'phone' => Phone::class,
            'plans' => Plans::class,
            'predefinedOptions' => PredefinedOptions::class,
            'relations' => Relations::class,
            'renderCache' => RenderCache::class,
            'rendering' => Rendering::class,
            'sentNotifications' => SentNotifications::class,
            'service' => Service::class,
            'statuses' => Statuses::class,
            'stencils' => Stencils::class,
            'submissions' => Submissions::class,
            'subscriptions' => Subscriptions::class,
            'syncs' => Syncs::class,
            'templates' => [
                'class' => Templates::class,
                'pluginClass' => Formie::class,
                'allowedTags' => $event->allowedTags,
                'allowedFilters' => $event->allowedFilters,
                'allowedFunctions' => $event->allowedFunctions,
                'allowedMethods' => $event->allowedMethods,
                'allowedProperties' => $event->allowedProperties,
            ],
            'tokens' => Tokens::class,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => FormsAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:4000/',
                'errorEntry' => 'js/main.js',
                'cacheKeySuffix' => '',
                'devServerInternal' => 'http://localhost:4000/',
                'checkDevServer' => true,
                'includeReactRefreshShim' => false,
            ],
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('formie');
    }
}
