<?php
namespace verbb\formie\web\assets\frontend;

use craft\web\AssetBundle;

class FrontendAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/formie/web/assets/frontend/dist';

        $this->js = [
            'js/formie.js',
        ];

        $this->css = [
            'css/formie-base.css',
            'css/formie-theme.css',
            'css/formie-base-layer.css',
            'css/formie-theme-layer.css',
        ];

        parent::init();
    }
}
