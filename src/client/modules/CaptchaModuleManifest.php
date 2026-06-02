<?php
namespace verbb\formie\client\modules;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

class CaptchaModuleManifest implements ModuleManifestProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $renderTarget = ClientModule::RENDER_TARGET_FRONTEND): array
    {
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true);

        if (!$captchas) {
            return [];
        }

        $context = new ClientModuleContext([
            'form' => $form,
            'renderTarget' => $renderTarget,
        ]);

        $modules = [];

        foreach ($captchas as $captcha) {
            $clientModule = $captcha->getClientModule($context);

            if (!$clientModule?->id) {
                continue;
            }

            if (!$clientModule->type) {
                $clientModule->type = $captcha->getType();
            }

            if (!$clientModule->targets) {
                $clientModule->targets = $context->getTargets();
            }

            if (!$clientModule->renderTargets) {
                $clientModule->renderTargets = [ClientModule::RENDER_TARGET_FRONTEND];
            }

            $modules[] = $clientModule;
        }

        return $modules;
    }
}
