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

        $this->depends = [
            CraftCpAsset::class,
        ];

        $this->js = [
            'js/vendor/Chart.bundle.min.js',
            'js/vendor/moment-with-locales.min.js',
            'js/vendor/chartjs-adapter-moment.min.js',
            'js/vendor/deepmerge.min.js',

            'js/formie-widgets.js',
        ];

        $this->css = [
            'css/formie-widgets.css',
        ];

        parent::init();
    }
}
