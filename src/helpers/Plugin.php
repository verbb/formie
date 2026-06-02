<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\web\assets\cp\CpReactAsset;
use verbb\formie\web\assets\cp\PluginSettingsAsset;
use verbb\formie\web\assets\cp\SentNotificationsAsset;
use verbb\formie\web\assets\cp\SubmissionsAsset;
use verbb\formie\web\assets\cp\WidgetsAsset;
use verbb\formie\web\assets\cp\WidgetsVendorAsset;

use Craft;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

use verbb\base\helpers\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    // Static Methods
    // =========================================================================

    public static function registerCpFormBuilderAssets(): void
    {
        self::registerCpAsset('src/form-builder/formie-form-builder.js');
    }

    public static function registerCpNewFormAssets(): void
    {
        self::registerCpAsset('src/new-form/formie-new-form.js');
    }

    public static function registerCpStencilNewAssets(): void
    {
        self::registerCpNewFormAssets();
    }

    public static function registerCpStencilEditAssets(): void
    {
        self::registerCpFormBuilderAssets();
    }

    public static function registerCpIntegrationConnectAssets(): void
    {
        self::registerCpAsset('src/integration-connect/formie-integration-connect.js');
    }

    public static function registerCpPluginSettingsAssets(): void
    {
        self::registerCpAsset('src/plugin-settings/js/formie-plugin-settings.js', CpReactAsset::class, PluginSettingsAsset::class);
    }

    public static function registerCpSubmissionsAssets(): void
    {
        self::registerCpAsset('src/submissions/js/formie-submissions.js', CpReactAsset::class, SubmissionsAsset::class);
    }

    public static function registerCpSentNotificationsAssets(): void
    {
        self::registerCpAsset('src/sent-notifications/js/formie-sent-notifications.js', CpReactAsset::class, SentNotificationsAsset::class);
    }

    public static function registerCpWidgetsAssets(): void
    {
        self::registerCpAsset('src/widgets/js/formie-widgets.js', WidgetsVendorAsset::class, WidgetsAsset::class);
    }

    public static function registerCpAsset(string $path, string|array $devDepends = CpReactAsset::class, ?string $productionBundleClass = null): void
    {
        $viteService = Formie::$plugin->getCpAssets();

        if (!$viteService->devServerRunning() && $productionBundleClass) {
            Craft::$app->getView()->registerAssetBundle($productionBundleClass);

            return;
        }

        $depends = is_array($devDepends) ? $devDepends : [$devDepends];

        $scriptOptions = [
            'depends' => $depends,
            'onload' => '',
        ];

        $styleOptions = [
            'depends' => $depends,
        ];

        $viteService->register($path, false, $scriptOptions, $styleOptions);

        // Provide nice build errors - only in dev
        if ($viteService->devServerRunning()) {
            $viteService->register('@vite/client', false);
        }
    }
}
