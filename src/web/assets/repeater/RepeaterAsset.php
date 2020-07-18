<?php
namespace verbb\formie\web\assets\repeater;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class RepeaterAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
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
