<?php
namespace verbb\formie\web\assets\cp;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;
use craft\web\assets\vue\VueAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class CpAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = '@verbb/formie/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CraftCpAsset::class,
        ];

        $this->js = [
            'js/formie-cp.js',
        ];

        $this->css = [
            'css/formie-cp.css',
        ];

        parent::init();
    }
}
