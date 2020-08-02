<?php
namespace verbb\formie\web\assets\captchas;

use Craft;
use craft\web\AssetBundle;

class RecaptchaV2CheckboxAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = '@verbb/formie/web/assets/captchas/dist';

        $this->js = [
            'js/recaptcha-v2-checkbox.js',
        ];

        parent::init();
    }
}
