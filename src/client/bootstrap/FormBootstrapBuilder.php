<?php
namespace verbb\formie\client\bootstrap;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\client\bootstrap\models\FormBootstrap;
use verbb\formie\client\models\LoadContext;

use yii\base\Component;

class FormBootstrapBuilder extends Component
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, LoadContext $context): FormBootstrap
    {
        $form = Formie::$plugin->getFormSiteOverrides()->applyToForm(
            clone $form,
            $context->siteId,
            true,
        );

        if ($context->draftContext !== null) {
            $form->setDraftContext($context->draftContext);
        }
        if ($context->grantToken) {
            Formie::$plugin->getSubmissionRequests()->exchangeGrant($form, $context->grantToken, $context->grantPurpose);
        }

        (new \verbb\formie\services\RuntimeConfiguration())->establish($form, $context->query);

        $definition = Formie::$plugin->getClientFormDefinitionBuilder()->build($form, $context);
        $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form, null, true);

        return new FormBootstrap([
            'definition' => $definition,
            'session' => $session,
        ]);
    }
}
