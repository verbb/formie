<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\web\assets\forms\FormsAsset;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

use verbb\base\helpers\Plugin as BasePlugin;

class Plugin extends BasePlugin
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
            'onload' => true,
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
