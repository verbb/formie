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

    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }
}

// Handle deprecated classes via an alias, until Formie 4
class_alias('verbb\formie\base\Field', 'verbb\formie\base\FormField');
class_alias('verbb\formie\base\FieldInterface', 'verbb\formie\base\FormFieldInterface');
class_alias('verbb\formie\fields\Address', 'verbb\formie\fields\formfields\Address');
class_alias('verbb\formie\fields\Agree', 'verbb\formie\fields\formfields\Agree');
class_alias('verbb\formie\fields\Calculations', 'verbb\formie\fields\formfields\Calculations');
class_alias('verbb\formie\fields\Categories', 'verbb\formie\fields\formfields\Categories');
class_alias('verbb\formie\fields\Checkboxes', 'verbb\formie\fields\formfields\Checkboxes');
class_alias('verbb\formie\fields\Date', 'verbb\formie\fields\formfields\Date');
class_alias('verbb\formie\fields\Dropdown', 'verbb\formie\fields\formfields\Dropdown');
class_alias('verbb\formie\fields\Email', 'verbb\formie\fields\formfields\Email');
class_alias('verbb\formie\fields\Entries', 'verbb\formie\fields\formfields\Entries');
class_alias('verbb\formie\fields\FileUpload', 'verbb\formie\fields\formfields\FileUpload');
class_alias('verbb\formie\fields\Group', 'verbb\formie\fields\formfields\Group');
class_alias('verbb\formie\fields\Heading', 'verbb\formie\fields\formfields\Heading');
class_alias('verbb\formie\fields\Hidden', 'verbb\formie\fields\formfields\Hidden');
class_alias('verbb\formie\fields\Html', 'verbb\formie\fields\formfields\Html');
class_alias('verbb\formie\fields\MissingField', 'verbb\formie\fields\formfields\MissingField');
class_alias('verbb\formie\fields\MultiLineText', 'verbb\formie\fields\formfields\MultiLineText');
class_alias('verbb\formie\fields\Name', 'verbb\formie\fields\formfields\Name');
class_alias('verbb\formie\fields\Number', 'verbb\formie\fields\formfields\Number');
class_alias('verbb\formie\fields\Password', 'verbb\formie\fields\formfields\Password');
class_alias('verbb\formie\fields\Payment', 'verbb\formie\fields\formfields\Payment');
class_alias('verbb\formie\fields\Phone', 'verbb\formie\fields\formfields\Phone');
class_alias('verbb\formie\fields\Products', 'verbb\formie\fields\formfields\Products');
class_alias('verbb\formie\fields\Radio', 'verbb\formie\fields\formfields\Radio');
class_alias('verbb\formie\fields\Recipients', 'verbb\formie\fields\formfields\Recipients');
class_alias('verbb\formie\fields\Repeater', 'verbb\formie\fields\formfields\Repeater');
class_alias('verbb\formie\fields\Section', 'verbb\formie\fields\formfields\Section');
class_alias('verbb\formie\fields\Signature', 'verbb\formie\fields\formfields\Signature');
class_alias('verbb\formie\fields\SingleLineText', 'verbb\formie\fields\formfields\SingleLineText');
class_alias('verbb\formie\fields\Summary', 'verbb\formie\fields\formfields\Summary');
class_alias('verbb\formie\fields\Table', 'verbb\formie\fields\formfields\Table');
class_alias('verbb\formie\fields\Tags', 'verbb\formie\fields\formfields\Tags');
class_alias('verbb\formie\fields\Users', 'verbb\formie\fields\formfields\Users');
class_alias('verbb\formie\fields\Variants', 'verbb\formie\fields\formfields\Variants');
