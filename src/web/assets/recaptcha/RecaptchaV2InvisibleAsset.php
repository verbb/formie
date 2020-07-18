<?php
namespace verbb\formie\web\assets\recaptcha;

use Craft;
use craft\web\AssetBundle;

class RecaptchaV2InvisibleAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = '@verbb/formie/web/assets/recaptcha/dist';

        $this->js = [
            'js/recaptcha-v2-invisible.js',
        ];

        parent::init();
    }
}
