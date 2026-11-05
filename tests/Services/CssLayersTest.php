<?php

use Tests\Support\WebRequestTestHelper;
use craft\helpers\FileHelper;
use craft\web\AssetManager;
use verbb\formie\Formie;
use verbb\formie\models\Settings;
use verbb\formie\services\BrowserAssets;
use verbb\formie\services\Rendering;
use verbb\formie\web\assets\frontend\FrontendAsset;

it('defaults to unlayered CSS', function() {
    expect((new Settings())->useCssLayers)->toBeFalse();
});

it('publishes both frontend variants without a loaded plugin', function() {
    $plugin = Formie::$plugin;
    $directory = Craft::$app->getPath()->getTempPath() . '/formie-cloud-assets-' . bin2hex(random_bytes(6));
    FileHelper::createDirectory($directory);

    try {
        Formie::$plugin = null;
        $bundle = new FrontendAsset();
        $bundle->publish(new AssetManager([
            'basePath' => $directory,
            'baseUrl' => '/cpresources',
            'linkAssets' => false,
        ]));

        expect($bundle->js)->toBe(['js/formie.js'])
            ->and($bundle->css)->toBe(['css/formie.css']);

        foreach (['', 'layered/'] as $prefix) {
            foreach (['js/formie.js', 'css/formie.css', 'css/formie-base.css', 'css/formie-theme.css'] as $file) {
                expect(is_file($bundle->basePath . '/' . $prefix . $file))->toBeTrue();
            }
        }
    } finally {
        Formie::$plugin = $plugin;
        FileHelper::removeDirectory($directory);
    }
});

it('selects matching styles and lazy runtime for every rendering entry point', function(bool $layered) {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->useCssLayers;
    $previousAssets = Formie::$plugin->getBrowserAssets();
    $form = formie()->form()->singleLineTextField('name')->signatureField('signature')->create();
    $settings->useCssLayers = $layered;
    $assets = new BrowserAssets();
    Formie::$plugin->set('browserAssets', $assets);

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($assets, $form, $layered) {
            $prefix = $layered ? '/layered' : '';
            $urls = $assets->getPublishedBrowserAssetUrls();
            $bundle = Craft::$app->getAssetManager()->getBundle(FrontendAsset::class);
            expect($bundle->js)->toBe([ltrim($prefix . '/js/formie.js', '/')])
                ->and($bundle->css)->toBe([ltrim($prefix . '/css/formie.css', '/')]);

            foreach (['css' => 'css/formie.css', 'baseStyles' => 'css/formie-base.css', 'themeStyles' => 'css/formie-theme.css', 'js' => 'js/formie.js'] as $key => $file) {
                expect($urls[$key])->toBe(rtrim($bundle->baseUrl, '/') . $prefix . '/' . $file)
                    ->and(is_file($bundle->basePath . $prefix . '/' . $file))->toBeTrue();
            }

            $assets->withPublishedBrowserAssets(function() use ($urls, $form) {
                $rendering = new Rendering();
                foreach ([
                    (string)$rendering->browserAssets(['inline' => true]),
                    (string)$rendering->renderCss(true) . (string)$rendering->renderJs(true),
                    (string)(new Rendering())->formAssets($form, ['inline' => true]),
                ] as $html) {
                    expect($html)->toContain($urls['baseStyles'], $urls['themeStyles'], $urls['js']);
                }

                $headRendering = new Rendering();
                $headRendering->renderCss();
                $head = implode('', Craft::$app->getView()->cssFiles);
                expect($head)->toContain($urls['baseStyles'], $urls['themeStyles']);
                expect((string)$headRendering->browserAssets(['inline' => true, 'theme' => 'none']))
                    ->toContain($urls['baseStyles'])->not->toContain($urls['themeStyles']);
            });

            $preview = $assets->getPublishedThemeCssContents();
            expect($preview)->toContain('.formie-input');
            expect(str_contains($preview, '@layer formie-'))->toBe($layered);
        });
    } finally {
        $settings->useCssLayers = $previous;
        Formie::$plugin->set('browserAssets', $previousAssets);
    }
})->with([false, true]);

it('only uses a dev server with matching CSS mode', function(bool $layered, bool $serverLayered) {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->useCssLayers;
    $general = Craft::$app->getConfig()->getGeneral();
    $previousDevMode = $general->devMode;
    $previousUrl = getenv('FORMIE_BROWSER_DEV_SERVER_INTERNAL');
    $directory = Craft::$app->getPath()->getTempPath() . '/formie-css-mode-' . bin2hex(random_bytes(6));
    mkdir($directory . '/__formie', 0777, true);
    file_put_contents($directory . '/__formie/css-mode', $serverLayered ? 'layered' : 'unlayered');
    putenv('FORMIE_BROWSER_DEV_SERVER_INTERNAL=file://' . $directory . '/');
    $settings->useCssLayers = $layered;
    $general->devMode = true;

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($layered, $serverLayered) {
            $assets = new BrowserAssets();
            $urls = $assets->getBrowserAssetUrls();
            expect($urls['viteClient'] !== null)->toBe($layered === $serverLayered);
            expect($urls['baseStyles'])->toContain($layered ? '/layered/css/' : '/css/');
            expect($assets->withPublishedBrowserAssets(fn() => $assets->getBrowserAssetUrls()['viteClient']))->toBeNull();
            expect($assets->getBrowserAssetUrls())->toBe($urls);
        });
    } finally {
        $settings->useCssLayers = $previous;
        $general->devMode = $previousDevMode;
        putenv($previousUrl === false ? 'FORMIE_BROWSER_DEV_SERVER_INTERNAL' : 'FORMIE_BROWSER_DEV_SERVER_INTERNAL=' . $previousUrl);
        unlink($directory . '/__formie/css-mode');
        rmdir($directory . '/__formie');
        rmdir($directory);
    }
})->with([[false, false], [false, true], [true, false], [true, true]]);
