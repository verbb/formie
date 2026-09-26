<?php
namespace verbb\formie\content;

use verbb\formie\Formie;

use Craft;
use craft\helpers\Json;

use LogicException;
use RuntimeException;

final class FieldStorageCodec
{
    // Static Methods
    // =========================================================================

    public static function encode(mixed $value, bool $encrypted): mixed
    {
        self::assertSafe($value);

        if (!$encrypted) {
            return $value;
        }

        return ['__formie_encrypted' => 1, 'ciphertext' => base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Formie::$plugin->getSettings()->getSecurityKey()))];
    }

    // Only trusted persisted-content hydration and field copying may call this method.
    public static function decode(mixed $value): mixed
    {
        if (is_array($value) && count($value) === 2 && array_key_exists('__formie_encrypted', $value) && array_key_exists('ciphertext', $value)) {
            if ($value['__formie_encrypted'] !== 1 || !is_string($value['ciphertext'])) {
                throw new RuntimeException('Unsupported or malformed stored Formie encryption envelope. Restore the original stored value before resaving.');
            }

            $bytes = base64_decode($value['ciphertext'], true);
            $plain = $bytes === false ? false : Craft::$app->getSecurity()->decryptByKey($bytes, Formie::$plugin->getSettings()->getSecurityKey());

            if ($plain === false) {
                throw new RuntimeException('Unable to decrypt the stored Formie field value. Restore the original security key or database backup.');
            }

            return Json::decode($plain);
        }

        // Formie 3's exact prefix and decoded marker, never a substring or a request heuristic.
        if (is_string($value) && str_starts_with($value, 'base64:')) {
            $bytes = base64_decode(substr($value, 7), true);

            if ($bytes !== false && str_starts_with($bytes, 'crypt:')) {
                $plain = Craft::$app->getSecurity()->decryptByKey(substr($bytes, 6), Formie::$plugin->getSettings()->getSecurityKey());

                if ($plain === false) {
                    throw new RuntimeException('Unable to decrypt the legacy Formie field value. Restore the original security key or database backup.');
                }

                return $plain;
            }
        }

        return $value;
    }

    public static function assertSafe(mixed $value): mixed
    {
        if ($value === null || is_string($value) || is_int($value) || is_bool($value) || (is_float($value) && is_finite($value))) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                self::assertSafe($item);
            }

            return $value;
        }

        throw new LogicException('Field projection returned ' . get_debug_type($value) . '. The owning field must explicitly project objects to JSON-safe scalars/arrays.');
    }
}
