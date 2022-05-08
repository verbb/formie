<?php
namespace verbb\formie\web\assets\forms;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class FormsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/formie/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CraftCpAsset::class,
        ];

        // $this->depends = [
        //     VerbbCpAsset::class,
        //     CpAsset::class,
        //     VueAsset::class,
        // ];

        // $this->js = [
        //     'js/main.js',
        // ];

        // $this->css = [
        //     'css/style.css',
        // ];

        parent::init();
    }
}
