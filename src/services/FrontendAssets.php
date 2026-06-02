<?php
namespace verbb\formie\services;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use verbb\formie\web\assets\frontend\FrontendAsset;

class FrontendAssets extends Component
{
    // Properties
    // =========================================================================

    private ?array $_browserAssetUrls = null;
    private ?bool $_devServerAvailable = null;


    // Public Methods
    // =========================================================================

    public function getBrowserAssetUrls(): array
    {
        if ($this->_browserAssetUrls !== null) {
            return $this->_browserAssetUrls;
        }

        if ($this->_shouldUseDevServer()) {
            $publicUrl = $this->_getDevServerPublicUrl();

            return $this->_browserAssetUrls = [
                'css' => null,
                'js' => "{$publicUrl}src/js/formie.ts",
                'viteClient' => "{$publicUrl}@vite/client",
            ];
        }

        $assetBundle = Craft::$app->getAssetManager()->getBundle(FrontendAsset::class);

        return $this->_browserAssetUrls = [
            'css' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . '/css/formie.css' : null,
            'js' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . '/js/formie.js' : null,
            'viteClient' => null,
        ];
    }


    // Private Methods
    // =========================================================================

    private function _shouldUseDevServer(): bool
    {
        if ($this->_devServerAvailable !== null) {
            return $this->_devServerAvailable;
        }

        if (!Craft::$app->getConfig()->getGeneral()->devMode) {
            return $this->_devServerAvailable = false;
        }

        $internalUrl = $this->_getDevServerInternalUrl();
        $context = stream_context_create([
            'http' => [
                'timeout' => 0.2,
            ],
        ]);

        return $this->_devServerAvailable = @file_get_contents("{$internalUrl}@vite/client", false, $context) !== false;
    }

    private function _getDevServerPublicUrl(): string
    {
        $url = App::parseEnv('$FORMIE_FRONTEND_DEV_SERVER_PUBLIC') ?: 'http://localhost:3902/';

        return rtrim($url, '/') . '/';
    }

    private function _getDevServerInternalUrl(): string
    {
        $url = App::parseEnv('$FORMIE_FRONTEND_DEV_SERVER_INTERNAL') ?: $this->_getDevServerPublicUrl();

        return rtrim($url, '/') . '/';
    }
}
