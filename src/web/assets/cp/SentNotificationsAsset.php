<?php
namespace verbb\formie\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class SentNotificationsAsset extends AssetBundle
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
            VerbbCpAsset::class,
            CraftCpAsset::class,
        ];

        $this->js = [
            'sent-notifications/js/formie-sent-notifications.js',
        ];

        $this->css = [
            'sent-notifications/css/formie-sent-notifications.css',
        ];

        parent::init();
    }
}
