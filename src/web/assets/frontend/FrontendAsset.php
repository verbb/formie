<?php
namespace verbb\formie\web\assets\frontend;

use verbb\formie\Formie;

use craft\web\AssetBundle;

class FrontendAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/formie/web/assets/frontend/dist';

        $prefix = Formie::$plugin->getSettings()->useCssLayers ? 'layered/' : '';

        $this->js = [
            $prefix . 'js/formie.js',
        ];

        $this->css = [
            $prefix . 'css/formie.css',
        ];

        parent::init();
    }
}
