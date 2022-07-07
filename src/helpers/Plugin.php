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

        $scriptOptions = [
            'depends' => [
                FormsAsset::class,
            ],
            'onload' => '',
        ];

        $styleOptions = [
            'depends' => [
                FormsAsset::class,
            ],
        ];

        $viteService->register($path, false, $scriptOptions, $styleOptions);

        // Provide nice build errors - only in dev
        if ($viteService->devServerRunning()) {
            $viteService->register('@vite/client', false);
        }
    }

}
