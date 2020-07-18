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
     * Control Panel routes.
     *
     * @return void
     */
    public function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['formie'] = 'formie/base/index';

            $event->rules['formie/forms'] = 'formie/forms/index';
            $event->rules['formie/forms/new'] = 'formie/forms/new';
            $event->rules['formie/forms/new/<siteHandle:{handle}>'] = 'formie/forms/new';
            $event->rules['formie/forms/edit/<formId:\d+>'] = 'formie/forms/edit';
            $event->rules['formie/forms/edit/<formId:\d+>/<siteHandle:{handle}>'] = 'formie/forms/edit';

            $event->rules['formie/submissions'] = 'formie/submissions/index';
            $event->rules['formie/submissions/new'] = 'formie/submissions/edit-submission';
            $event->rules['formie/submissions/new/<siteHandle:{handle}>'] = 'formie/submissions/edit-submission';
            $event->rules['formie/submissions/edit/<submissionId:\d+>'] = 'formie/submissions/edit-submission';
            $event->rules['formie/submissions/edit/<submissionId:\d+>/<siteHandle:{handle}>'] = 'formie/submissions/edit-submission';

            $event->rules['formie/settings'] = 'formie/settings/index';
            $event->rules['formie/settings/general'] = 'formie/settings/index';
            $event->rules['formie/settings/submissions'] = 'formie/settings/submissions';
            $event->rules['formie/settings/spam'] = 'formie/settings/spam';
            $event->rules['formie/settings/notifications'] = 'formie/notifications/index';
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
            $event->rules['formie/settings/security'] = 'formie/security/index';
            $event->rules['formie/settings/privacy'] = 'formie/privacy/index';
            $event->rules['formie/settings/integrations'] = 'formie/integrations/index';
            $event->rules['formie/settings/permissions'] = 'formie/permissions/index';
            $event->rules['formie/settings/fields'] = 'formie/fields/index';
        });
    }
}

