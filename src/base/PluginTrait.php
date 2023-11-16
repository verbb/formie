<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\services\Emails;
use verbb\formie\services\EmailTemplates;
use verbb\formie\services\Fields;
use verbb\formie\services\Forms;
use verbb\formie\services\FormTemplates;
use verbb\formie\services\Integrations;
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

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use nystudio107\pluginvite\services\VitePluginService;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Formie $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('formie');

        return [
            'components' => [
                'emails' => Emails::class,
                'emailTemplates' => EmailTemplates::class,
                'fields' => Fields::class,
                'forms' => Forms::class,
                'formTemplates' => FormTemplates::class,
                'integrations' => Integrations::class,
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
            ],
        ];
    }


    // Public Methods
    // =========================================================================

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

    public function getTokens(): Tokens
    {
        return $this->get('tokens');
    }

    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }
}
