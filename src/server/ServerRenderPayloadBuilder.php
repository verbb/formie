<?php
namespace verbb\formie\server;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use yii\base\Component;

class ServerRenderPayloadBuilder extends Component
{
    // Public Methods
    // =========================================================================

    public function buildServerRenderPayload(Form $form, array $renderOptions = [], array $populateFormValues = []): array
    {
        $form->setActionUrl('formie/server/submissions/submit');

        if ($populateFormValues) {
            Formie::$plugin->getRendering()->populateFormValues($form, $populateFormValues);
        }

        return [
            'html' => (string)Formie::$plugin->getRendering()->renderForm($form, $renderOptions),
        ];
    }

    public function buildRefreshTokensPayload(Form $form): array
    {
        return Formie::$plugin->getClientSessionService()->buildTokenPayload($form, true);
    }
}
