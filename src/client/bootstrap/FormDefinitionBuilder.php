<?php
namespace verbb\formie\client\bootstrap;

use verbb\formie\client\bootstrap\models\FormDefinition;
use verbb\formie\client\models\LoadContext;
use verbb\formie\elements\Form;

use yii\base\Component;

class FormDefinitionBuilder extends Component
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, LoadContext $context): FormDefinition
    {
        return $form->getClientRenderedDefinition($context);
    }
}
