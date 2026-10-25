<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;

class RetiredCaptchas
{
    // Static Methods
    // =========================================================================

    public static function isRetired(string $handle, ?string $type = null): bool
    {
        // Explicit third-party types take precedence over the former built-in handles.
        if ($type !== null && $type !== '') {
            return in_array(ltrim($type, '\\'), self::TYPES, true);
        }

        return isset(self::TYPES[$handle]);
    }

    public static function removeBindings(array $settings, ?array $providerTypes = null): array
    {
        if (empty($settings['integrations']) || !is_array($settings['integrations'])) {
            return $settings;
        }

        foreach ($settings['integrations'] as $handle => $binding) {
            $type = $binding['type'] ?? null;

            // Form bindings normally omit their type. Retain unavailable custom providers
            // even when they reuse one of the retired handles.
            if (!$type && isset(self::TYPES[$handle])) {
                if ($providerTypes !== null) {
                    $type = $providerTypes[$handle] ?? null;
                } elseif (Formie::$plugin->isInstalled) {
                    // The provider table may not exist yet during an upgrade. Let
                    // the migration resolve untyped bindings from its known schema.
                    if (Craft::$app->getPlugins()->isPluginUpdatePending(Formie::$plugin)) {
                        continue;
                    }

                    $type = Formie::$plugin->getCaptchaProviders()->getProviderRowByHandle($handle)['type'] ?? null;
                }
            }

            if (self::isRetired($handle, $type)) {
                unset($settings['integrations'][$handle]);
            }
        }

        return $settings;
    }


    // Constants
    // =========================================================================

    // Historical class names must remain strings: these integrations no longer exist.
    public const TYPES = [
        'duplicate' => 'verbb\\formie\\integrations\\captchas\\Duplicate',
        'honeypot' => 'verbb\\formie\\integrations\\captchas\\Honeypot',
        'javascript' => 'verbb\\formie\\integrations\\captchas\\Javascript',
    ];
}
