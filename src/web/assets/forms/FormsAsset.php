<?php
namespace verbb\formie\web\assets\forms;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class FormsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
            VueAsset::class,
        ];

        $this->js = [
            'js/main.js',
        ];

        $this->css = [
            'css/style.css',
        ];

        parent::init();
    }
}
