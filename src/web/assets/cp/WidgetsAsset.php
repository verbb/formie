<?php
namespace verbb\formie\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

class WidgetsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/formie/web/assets/cp/dist';
        
        $this->jsOptions = [
            'type' => 'module',
        ];

        $this->depends = [
            WidgetsVendorAsset::class,
            CraftCpAsset::class,
        ];

        $this->js = [
            'widgets/js/formie-widgets.js',
        ];

        $this->css = [
            'widgets/css/formie-widgets.css',
        ];

        parent::init();
    }
}
