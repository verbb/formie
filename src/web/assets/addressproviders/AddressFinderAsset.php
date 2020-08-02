<?php
namespace verbb\formie\web\assets\addressproviders;

use Craft;
use craft\web\AssetBundle;

class AddressFinderAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = '@verbb/formie/web/assets/addressproviders/dist';

        $this->js = [
            'js/address-finder.js',
        ];

        parent::init();
    }
}
