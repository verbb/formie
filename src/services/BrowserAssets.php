<?php
namespace verbb\formie\services;

use verbb\formie\web\assets\frontend\FrontendAsset;

use Craft;
use craft\base\Component;
use craft\helpers\App;

class BrowserAssets extends Component
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

        $published = $this->getPublishedBrowserAssetUrls();

        if ($this->_shouldUseDevServer()) {
            $publicUrl = $this->_getDevServerPublicUrl();

            // Keep the published CSS URL even in Vite mode. The entry JS imports CSS for
            // HMR, but production formie.js does not re-inject formie.css — if the Vite
            // script fails to load in the browser (common for CP preview iframes), a null
            // css URL left forms completely unstyled.
            return $this->_browserAssetUrls = [
                'css' => $published['css'],
                'baseStyles' => $published['baseStyles'],
                'themeStyles' => $published['themeStyles'],
                'js' => "{$publicUrl}src/js/formie.ts",
                'viteClient' => "{$publicUrl}@vite/client",
            ];
        }

        return $this->_browserAssetUrls = $published;
    }

    public function getPublishedBrowserAssetUrls(): array
    {
        $assetBundle = Craft::$app->getAssetManager()->getBundle(FrontendAsset::class);

        return [
            // Keep the stable combined stylesheet URL for extensions that consume
            // this service directly. Core rendering uses the split assets below.
            'css' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . '/css/formie.css' : null,
            'baseStyles' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . '/css/formie-base.css' : null,
            'themeStyles' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . '/css/formie-theme.css' : null,
            'js' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . '/js/formie.js' : null,
            'viteClient' => null,
        ];
    }

    public function withPublishedBrowserAssets(callable $callback): mixed
    {
        $previous = $this->_browserAssetUrls;
        $this->_browserAssetUrls = $this->getPublishedBrowserAssetUrls();

        try {
            return $callback();
        } finally {
            $this->_browserAssetUrls = $previous;
        }
    }

    public function getPublishedThemeCssContents(): string
    {
        $paths = [
            Craft::getAlias('@verbb/formie/web/assets/frontend/dist/css/formie-base.css'),
            Craft::getAlias('@verbb/formie/web/assets/frontend/dist/css/formie-theme.css'),
        ];
        $contents = [];

        foreach ($paths as $path) {
            if (!is_string($path) || !is_file($path)) {
                continue;
            }

            $css = file_get_contents($path);

            if (is_string($css)) {
                $contents[] = $css;
            }
        }

        return implode(PHP_EOL, $contents);
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
        $url = App::parseEnv('$FORMIE_BROWSER_DEV_SERVER_PUBLIC') ?: 'http://localhost:3902/';

        return rtrim($url, '/') . '/';
    }

    private function _getDevServerInternalUrl(): string
    {
        $url = App::parseEnv('$FORMIE_BROWSER_DEV_SERVER_INTERNAL') ?: $this->_getDevServerPublicUrl();

        return rtrim($url, '/') . '/';
    }
}
