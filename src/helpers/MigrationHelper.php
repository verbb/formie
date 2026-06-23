<?php
namespace verbb\formie\helpers;

use Craft;
use craft\base\PluginInterface;

class MigrationHelper
{
    // Static Methods
    // =========================================================================

    public static function savePluginSettingsIfAllowed(PluginInterface $plugin, array $settings): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return true;
        }

        return Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);
    }
}
