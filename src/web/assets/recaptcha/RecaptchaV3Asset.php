<?php
namespace verbb\formie\web\assets\recaptcha;

use Craft;
use craft\web\AssetBundle;

class RecaptchaV3Asset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = '@verbb/formie/web/assets/recaptcha/dist';

        $this->js = [
            'js/recaptcha-v3.js',
        ];

        parent::init();
    }
}
