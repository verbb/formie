<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\services\Emails;
use verbb\formie\services\EmailTemplates;
use verbb\formie\services\Fields;
use verbb\formie\services\Forms;
use verbb\formie\services\FormTemplates;
use verbb\formie\services\Integrations;
use verbb\formie\services\NestedFields;
use verbb\formie\services\Notifications;
use verbb\formie\services\Phone;
use verbb\formie\services\Relations;
use verbb\formie\services\RenderCache;
use verbb\formie\services\Rendering;
use verbb\formie\services\SentNotifications;
use verbb\formie\services\Service;
use verbb\formie\services\Statuses;
use verbb\formie\services\Stencils;
use verbb\formie\services\Submissions;
use verbb\formie\services\Syncs;
use verbb\formie\services\Tokens;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;


trait PluginTrait
{
    // Static Properties
    // =========================================================================

    /**
     * @var Formie
     */
    public static $plugin;


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

    public function getNestedFields(): NestedFields
    {
        return $this->get('nestedFields');
    }

    public function getNotifications(): Notifications
    {
        return $this->get('notifications');
    }

    public function getPhone(): Phone
    {
        return $this->get('phone');
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

    public function getSyncs(): Syncs
    {
        return $this->get('syncs');
    }

    public function getTokens(): Tokens
    {
        return $this->get('tokens');
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'formie');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'formie');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'emails' => Emails::class,
            'emailTemplates' => EmailTemplates::class,
            'fields' => Fields::class,
            'forms' => Forms::class,
            'formTemplates' => FormTemplates::class,
            'integrations' => Integrations::class,
            'nestedFields' => NestedFields::class,
            'notifications' => Notifications::class,
            'phone' => Phone::class,
            'relations' => Relations::class,
            'renderCache' => RenderCache::class,
            'rendering' => Rendering::class,
            'sentNotifications' => SentNotifications::class,
            'service' => Service::class,
            'statuses' => Statuses::class,
            'stencils' => Stencils::class,
            'submissions' => Submissions::class,
            'syncs' => Syncs::class,
            'tokens' => Tokens::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging()
    {
        BaseHelper::setFileLogging('formie');
    }
}
