<?php
namespace verbb\formie\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;
use craft\web\View;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class CpReactAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/formie/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CraftCpAsset::class,
        ];

        parent::init();
    }
}
