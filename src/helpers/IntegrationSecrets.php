<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\attributes\Sensitive;
use verbb\formie\base\IntegrationInterface;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;

use RuntimeException;
use ReflectionObject;
use ReflectionProperty;

/** Encrypt literal connection settings while leaving environment references portable. */
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

    public static function protectBindings(array $bindings): array
    {
        $service = Formie::$plugin->getIntegrations();

        foreach ($bindings as $handle => &$settings) {
            if (!is_array($settings)) {
                continue;
            }
            $integration = $service->getIntegrationByHandle((string)$handle) ?? $service->getCaptchaByHandle((string)$handle);
            $settings = self::protect($settings, sensitiveAttributes: $integration ? self::sensitiveAttributes($integration) : []);
        }
        return $bindings;
    }

    public static function protect(array $settings, bool $connection = false, array $sensitiveAttributes = []): array
    {
        foreach ($settings as $key => &$value) {
            $sensitive = $connection || in_array($key, $sensitiveAttributes, true) || preg_match('/password|secret|token|authorization|cookie|api.?key|httpAuth|credential|webhook|headers|url$/i', (string)$key);

            if (is_array($value)) {
                $value = self::protect($value, (bool)$sensitive);
            } elseif ($sensitive && is_string($value) && $value !== '' && !str_starts_with($value, self::PREFIX) && !preg_match('/^\$[A-Z][A-Z0-9_]*$/D', $value)) {
                $value = self::PREFIX . base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Formie::$plugin->getSettings()->getSecurityKey()));
            }
        }
        return $settings;
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
                $plain = $cipher === false ? false : Craft::$app->getSecurity()->decryptByKey($cipher, Formie::$plugin->getSettings()->getSecurityKey());

                if ($plain === false) {
                    throw new RuntimeException('Unable to decrypt integration settings. Restore the configured security key.');
                }
                $value = Json::decode($plain);
            }
        }
        return $settings;
    }


    // Constants
    // =========================================================================

    private const PREFIX = 'formie-secret:v1:';
}
