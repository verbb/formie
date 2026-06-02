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
        $definition = Formie::$plugin->getClientFormDefinitionBuilder()->build($form, $context);
        $session = Formie::$plugin->getClientSessionService()->issueInitialSession($form, null, true);

        return new FormBootstrap([
            'definition' => $definition,
            'session' => $session,
        ]);
    }
}
