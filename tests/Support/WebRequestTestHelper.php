<?php

declare(strict_types=1);

namespace Tests\Support;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\i18n\PhpMessageSource;
use craft\helpers\ArrayHelper;
use craft\services\Config;
use craft\web\Application;
use verbb\formie\Formie;

final class WebRequestTestHelper
{
    public static function withWebRequestContext(callable $callback, array $options = []): mixed
    {
        $originalApp = Craft::$app;
        $originalPlugin = Formie::$plugin ?? null;
        $originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $originalServerName = $_SERVER['SERVER_NAME'] ?? null;
        $originalHttpHost = $_SERVER['HTTP_HOST'] ?? null;
        $originalHttps = $_SERVER['HTTPS'] ?? null;
        $originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

        $_SERVER['REQUEST_METHOD'] = strtoupper((string)($options['method'] ?? 'GET'));
        $_SERVER['SERVER_NAME'] = (string)($options['serverName'] ?? 'craft.example.test');
        $_SERVER['HTTP_HOST'] = (string)($options['httpHost'] ?? $_SERVER['SERVER_NAME']);
        $_SERVER['HTTPS'] = (string)($options['https'] ?? 'on');
        $_SERVER['REQUEST_URI'] = (string)($options['requestUri'] ?? '/actions/formie');
        $_SERVER['REMOTE_ADDR'] = (string)($options['remoteAddr'] ?? '127.0.0.1');

        try {
            /** @var Application $app */
            $app = self::createWebApplication($originalApp);
            self::mirrorFormiePluginState($originalApp, $app);
            Formie::$plugin = $originalPlugin;
            self::registerFormieTranslations($app, $originalPlugin?->getBasePath());

            $request = $app->getRequest();
            $response = $app->getResponse();
            $session = $app->getSession();

            $request->setQueryParams((array)($options['queryParams'] ?? []));
            $request->setBodyParams((array)($options['bodyParams'] ?? []));
            $request->setHostInfo((string)($options['hostInfo'] ?? 'https://craft.example.test'));

            foreach ((array)($options['headers'] ?? []) as $name => $value) {
                $request->getHeaders()->set((string)$name, (string)$value);
            }

            $session->open();

            $originalCraftApp = Craft::$app;
            Craft::$app = $app;

            try {
                return $callback($request, $response, $session);
            } finally {
                Craft::$app = $originalCraftApp;
            }
        } finally {
            if (isset($session) && $session->getIsActive()) {
                $session->destroy();
                $session->close();
            }

            if (isset($app)) {
                $app->getErrorHandler()->unregister();
            }

            Craft::$app = $originalApp;
            Formie::$plugin = $originalPlugin;
            $originalApp->getErrorHandler()->register();

            if ($originalRequestMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $originalRequestMethod;
            }

            if ($originalServerName === null) {
                unset($_SERVER['SERVER_NAME']);
            } else {
                $_SERVER['SERVER_NAME'] = $originalServerName;
            }

            if ($originalHttpHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $originalHttpHost;
            }

            if ($originalHttps === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $originalHttps;
            }

            if ($originalRequestUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $originalRequestUri;
            }

            if ($originalRemoteAddr === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $originalRemoteAddr;
            }
        }
    }

    private static function createWebApplication(ConsoleApplication $originalApp): Application
    {
        $originalConfig = $originalApp->getConfig();
        $configService = new Config();
        $configService->appType = 'web';
        $configService->env = $originalConfig->env;
        $configService->configDir = $originalConfig->configDir;
        $configService->appDefaultsDir = $originalConfig->appDefaultsDir;

        $config = ArrayHelper::merge(
            [
                'vendorPath' => CRAFT_VENDOR_PATH,
                'env' => $configService->env,
                'components' => [
                    'config' => $configService,
                ],
            ],
            require CRAFT_VENDOR_PATH . '/craftcms/cms/src/config/app.php',
            require CRAFT_VENDOR_PATH . '/craftcms/cms/src/config/app.web.php',
        );

        $localConfig = ArrayHelper::merge(
            $configService->getConfigFromFile('app'),
            $configService->getConfigFromFile('app.web'),
        );

        $config = ArrayHelper::merge($config, $localConfig);

        if (function_exists('craft_modify_app_config')) {
            craft_modify_app_config($config, 'web');
        }

        return Craft::createObject($config);
    }

    private static function mirrorFormiePluginState(ConsoleApplication $sourceApp, Application $targetApp): void
    {
        $sourcePlugins = $sourceApp->getPlugins();

        if (!$sourcePlugins->isPluginEnabled('formie') || !$sourcePlugins->getPlugin('formie')) {
            return;
        }

        $targetPlugins = $targetApp->getPlugins();
        $sourceReflection = new \ReflectionClass($sourcePlugins);
        $targetReflection = new \ReflectionClass($targetPlugins);

        foreach (['_plugins', '_storedPluginInfo'] as $propertyName) {
            if (!$sourceReflection->hasProperty($propertyName) || !$targetReflection->hasProperty($propertyName)) {
                continue;
            }

            $sourceProperty = $sourceReflection->getProperty($propertyName);
            $sourceProperty->setAccessible(true);
            $value = $sourceProperty->getValue($sourcePlugins);

            $targetProperty = $targetReflection->getProperty($propertyName);
            $targetProperty->setAccessible(true);
            $targetProperty->setValue($targetPlugins, $value);
        }

        foreach (['_pluginsLoaded' => true, '_loadingPlugins' => false] as $propertyName => $value) {
            if (!$targetReflection->hasProperty($propertyName)) {
                continue;
            }

            $targetProperty = $targetReflection->getProperty($propertyName);
            $targetProperty->setAccessible(true);
            $targetProperty->setValue($targetPlugins, $value);
        }
    }

    private static function registerFormieTranslations(Application $app, ?string $pluginBasePath): void
    {
        if (!$pluginBasePath) {
            return;
        }

        $app->getI18n()->translations['formie'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en-US',
            'basePath' => $pluginBasePath . DIRECTORY_SEPARATOR . 'translations',
            'forceTranslation' => true,
            'allowOverrides' => true,
        ];
    }
}
