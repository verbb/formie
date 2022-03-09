<?php
namespace verbb\formie\web\assets\widgets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class WidgetsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/vendor/chart-js/Chart.bundle.min.js',
            'js/vendor/moment/moment-with-locales.min.js',
            'js/vendor/chartjs-adapter-moment/chartjs-adapter-moment.min.js',
            'js/vendor/deepmerge/umd.js',
            'js/formie-widgets.js',
        ];

        $this->css = [
            'css/formie-widgets.css',
        ];

        parent::init();
    }
}
