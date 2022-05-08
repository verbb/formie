<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\web\assets\forms\FormsAsset;

class Plugin
{
    // Static Methods
    // =========================================================================

    public static function registerAsset(string $path): void
    {
        $viteService = Formie::$plugin->getVite();

        $options = [
            'depends' => [
                FormsAsset::class,
            ],
            'onload' => "e=new CustomEvent('vite-script-loaded', {detail:{path: '$path'}});document.dispatchEvent(e);",
        ];

        $viteService->register($path, false, $options, $options);

        // Provide nice build errors - only in dev
        if ($viteService->devServerRunning()) {
            $viteService->register('@vite/client', false);
        }
    }

}
