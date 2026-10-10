<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\content\ContentEncryption;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use ReflectionObject;
use ReflectionProperty;
use RuntimeException;

/** Reads beta encrypted settings while new settings remain literal or environment references. */
final class IntegrationSecrets
{
    // Static Methods
    // =========================================================================

    public static function sensitiveAttributes(IntegrationInterface $integration): array
    {
        $attributes = [];

        foreach ((new ReflectionObject($integration))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->getAttributes(Sensitive::class)) {
                $attributes[] = $property->getName();
            }
        }
        return $attributes;
    }

    public static function removeSensitiveValues(array $settings, IntegrationInterface $integration): array
    {
        $secrets = [];

        foreach (self::sensitiveAttributes($integration) as $attribute) {
            self::_collectSensitiveValues($integration->{$attribute} ?? null, $secrets);
        }

        return self::_removeSensitiveValues($settings, array_values(array_unique($secrets)));
    }

    public static function protectBindings(array $bindings): array
    {
        return $bindings;
    }

    public static function protect(array $settings, bool $connection = false, array $sensitiveAttributes = []): array
    {
        return $settings;
    }

    public static function containsUnavailable(mixed $value): bool
    {
        if (is_string($value)) {
            return str_starts_with($value, self::PREFIX);
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (self::containsUnavailable($item)) {
                return true;
            }
        }

        return false;
    }

    public static function assertAvailable(array $settings): void
    {
        if (self::containsUnavailable($settings)) {
            throw new RuntimeException('This integration contains beta-encrypted settings that cannot be decrypted in this environment. Restore the former key or replace the unavailable setting.');
        }
    }

    public static function resolveFormValue(string $value): string
    {
        if (preg_match('/^\$([A-Z][A-Z0-9_]*)$/D', $value, $match)) {
            if (!in_array($match[1], Formie::$plugin->getSettings()->referenceEnvironmentAllowlist, true)) {
                throw new RuntimeException('This environment reference is not permitted in form settings.');
            }
            return (string)App::env($match[1]);
        }
        return $value;
    }

    public static function redactValues(array $data, array $secrets): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = self::redactValues($value, $secrets);
            } elseif (is_string($value)) {
                foreach ($secrets as $secret) {
                    if (is_string($secret) && $secret !== '') {
                        $value = str_replace($secret, '[redacted]', $value);
                    }
                }
            }
        }
        return $data;
    }

    public static function reveal(array $settings): array
    {
        foreach ($settings as &$value) {
            if (is_array($value)) {
                $value = self::reveal($value);
            } elseif (is_string($value) && str_starts_with($value, self::PREFIX)) {
                $cipher = base64_decode(substr($value, strlen(self::PREFIX)), true);
                $plain = false;

                if ($cipher !== false) {
                    foreach (ContentEncryption::legacyKeys() as $key) {
                        $plain = Craft::$app->getSecurity()->decryptByKey($cipher, $key);

                        if ($plain !== false) {
                            break;
                        }
                    }
                }

                if ($plain !== false) {
                    $value = Json::decode($plain);
                }
            }
        }
        return $settings;
    }


    // Private Methods
    // =========================================================================

    private static function _collectSensitiveValues(mixed $value, array &$secrets): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                self::_collectSensitiveValues($item, $secrets);
            }

            return;
        }

        if (!is_string($value) || $value === '') {
            return;
        }

        $secrets[] = $value;
        $parsed = App::parseEnv($value);

        if (is_string($parsed) && $parsed !== '') {
            $secrets[] = $parsed;
        }
    }

    private static function _removeSensitiveValues(array $settings, array $secrets): array
    {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $settings[$key] = self::_removeSensitiveValues($value, $secrets);
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            foreach ($secrets as $secret) {
                if ($value === $secret || (strlen($secret) >= 8 && str_contains($value, $secret))) {
                    unset($settings[$key]);
                    break;
                }
            }
        }

        return $settings;
    }


    // Constants
    // =========================================================================

    private const PREFIX = 'formie-secret:v1:';
}
