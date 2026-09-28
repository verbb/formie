<?php
namespace verbb\formie\client\modules;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;

class CaptchaModuleProvider implements BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModule::SURFACE_SERVER_RENDERED): array
    {
        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form, null, true);

        if (!$captchas) {
            return [];
        }

        $context = new BrowserModuleContext([
            'form' => $form,
            'surface' => $surface,
        ]);

        $modules = [];

        foreach ($captchas as $captcha) {
            $browserModule = $captcha->getBrowserModule($context);

            if (!$browserModule?->moduleId) {
                continue;
            }

            $modules[] = $browserModule->withProjectionDefaults('captcha', $context->getTargets());
        }

        return $modules;
    }
}
