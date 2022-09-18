<?php
namespace verbb\formie\base;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

use yii\base\Event;

trait Routes
{
    // Private Methods
    // =========================================================================
    
    /**
     * Site routes.
     *
     * @return void
     */
    public function _registerSiteRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['formie/integrations/callback'] = 'formie/integrations/callback';
            $event->rules['formie/payment-webhooks/process-webhook'] = 'formie/payment-webhooks/process-webhook';
        });
    }
    
    /**
     * Control Panel routes.
     *
     * @return void
     */
    public function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['formie'] = 'formie/base/index';

            $event->rules['formie/forms'] = 'formie/forms/index';
            $event->rules['formie/forms/new'] = 'formie/forms/new';
            $event->rules['formie/forms/new/<siteHandle:{handle}>'] = 'formie/forms/new';
            $event->rules['formie/forms/edit/<formId:\d+>'] = 'formie/forms/edit';

            $event->rules['formie/submissions'] = 'formie/submissions/index';
            $event->rules['formie/submissions/<formHandle:{handle}>'] = 'formie/submissions/index';
            $event->rules['formie/submissions/<formHandle:{handle}>/new'] = 'formie/submissions/edit-submission';
            $event->rules['formie/submissions/<formHandle:{handle}>/<submissionId:\d+>'] = 'formie/submissions/edit-submission';

            $event->rules['formie/sent-notifications'] = 'formie/sent-notifications/index';
            $event->rules['formie/sent-notifications/edit/<sentNotificationId:\d+>'] = 'formie/sent-notifications/edit';

            $event->rules['formie/settings'] = 'formie/settings/index';
            $event->rules['formie/settings/general'] = 'formie/settings/index';
            $event->rules['formie/settings/forms'] = 'formie/settings/forms';
            $event->rules['formie/settings/fields'] = 'formie/settings/fields';
            $event->rules['formie/settings/submissions'] = 'formie/settings/submissions';
            $event->rules['formie/settings/spam'] = 'formie/settings/spam';
            $event->rules['formie/settings/notifications'] = 'formie/notifications/index';
            $event->rules['formie/settings/sent-notifications'] = 'formie/sent-notifications/settings';
            $event->rules['formie/settings/statuses'] = 'formie/statuses/index';
            $event->rules['formie/settings/statuses/new'] = 'formie/statuses/edit';
            $event->rules['formie/settings/statuses/edit/<id:\d+>'] = 'formie/statuses/edit';
            $event->rules['formie/settings/stencils'] = 'formie/stencils/index';
            $event->rules['formie/settings/stencils/new'] = 'formie/stencils/new';
            $event->rules['formie/settings/stencils/edit/<id:\d+>'] = 'formie/stencils/edit';
            $event->rules['formie/settings/form-templates'] = 'formie/form-templates/index';
            $event->rules['formie/settings/form-templates/new'] = 'formie/form-templates/edit';
            $event->rules['formie/settings/form-templates/edit/<id:\d+>'] = 'formie/form-templates/edit';
            $event->rules['formie/settings/email-templates'] = 'formie/email-templates/index';
            $event->rules['formie/settings/email-templates/new'] = 'formie/email-templates/edit';
            $event->rules['formie/settings/email-templates/edit/<id:\d+>'] = 'formie/email-templates/edit';
            $event->rules['formie/settings/pdf-templates'] = 'formie/pdf-templates/index';
            $event->rules['formie/settings/pdf-templates/new'] = 'formie/pdf-templates/edit';
            $event->rules['formie/settings/pdf-templates/edit/<id:\d+>'] = 'formie/pdf-templates/edit';
            $event->rules['formie/settings/security'] = 'formie/security/index';
            $event->rules['formie/settings/privacy'] = 'formie/privacy/index';
            $event->rules['formie/settings/captchas'] = 'formie/integration-settings/captcha-index';
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
            $event->rules['formie/settings/payments'] = 'formie/integration-settings/payment-index';
            $event->rules['formie/settings/payments/new'] = 'formie/integration-settings/edit-payment';
            $event->rules['formie/settings/payments/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-payment';
            $event->rules['formie/settings/webhooks'] = 'formie/integration-settings/webhook-index';
            $event->rules['formie/settings/webhooks/new'] = 'formie/integration-settings/edit-webhook';
            $event->rules['formie/settings/webhooks/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-webhook';
            $event->rules['formie/settings/miscellaneous'] = 'formie/integration-settings/miscellaneous-index';
            $event->rules['formie/settings/miscellaneous/new'] = 'formie/integration-settings/edit-miscellaneous';
            $event->rules['formie/settings/miscellaneous/edit/<integrationId:\d+>'] = 'formie/integration-settings/edit-miscellaneous';
            $event->rules['formie/settings/support'] = 'formie/support/index';
            $event->rules['formie/settings/import-export'] = 'formie/import-export/index';
            $event->rules['formie/settings/import-export/import-configure/<filename:.*>'] = 'formie/import-export/import-configure';
            $event->rules['formie/settings/import-export/import-completed/<formId:\d+>'] = 'formie/import-export/import-completed';
        });
    }
}

