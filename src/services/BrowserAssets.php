<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
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

            // Keep published main styles in Vite mode too. Lazy field modules only
            // inject their own CSS, and preview iframes may not reach the dev server.
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

        $prefix = Formie::$plugin->getSettings()->useCssLayers ? '/layered' : '';

        return [
            // Keep the stable combined stylesheet URL for extensions that consume
            // this service directly. Core rendering uses the split assets below.
            'css' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . $prefix . '/css/formie.css' : null,
            'baseStyles' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . $prefix . '/css/formie-base.css' : null,
            'themeStyles' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . $prefix . '/css/formie-theme.css' : null,
            'js' => $assetBundle ? rtrim($assetBundle->baseUrl, '/') . $prefix . '/js/formie.js' : null,
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
        $prefix = Formie::$plugin->getSettings()->useCssLayers ? '/layered' : '';
        $paths = [
            Craft::getAlias('@verbb/formie/web/assets/frontend/dist' . $prefix . '/css/formie-base.css'),
            Craft::getAlias('@verbb/formie/web/assets/frontend/dist' . $prefix . '/css/formie-theme.css'),
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

        // Never mix a dev runtime's inline field CSS with the other published mode.
        $expectedMode = Formie::$plugin->getSettings()->useCssLayers ? 'layered' : 'unlayered';

        return $this->_devServerAvailable = @file_get_contents("{$internalUrl}__formie/css-mode", false, $context) === $expectedMode;
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
