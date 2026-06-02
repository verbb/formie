<?php
namespace verbb\formie\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

class WidgetsVendorAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/formie/web/assets/cp/src/widgets/js/vendor';

        $this->depends = [
            CraftCpAsset::class,
        ];

        $this->js = [
            'Chart.bundle.min.js',
            'moment-with-locales.min.js',
            'chartjs-adapter-moment.min.js',
            'deepmerge.min.js',
        ];

        parent::init();
    }
}
