<?php
namespace verbb\formie\content;

use verbb\formie\Formie;

use Craft;
use craft\helpers\Json;

use LogicException;
use RuntimeException;

final class FieldStorageCodec
{
    private const PLAIN_ENVELOPE_MARKER = 'formie:plain:v1:8d986bd501b14ae6a8de160e74b602db';

    // Static Methods
    // =========================================================================

    public static function encode(mixed $value, bool $encrypted): mixed
    {
        self::assertSafe($value);

        if (!$encrypted) {
            if (self::_legacyCiphertext($value) !== null || self::_isPlainEnvelopeShape($value) || self::_isEncryptedEnvelopeShape($value)) {
                return ['__formie_storage' => self::PLAIN_ENVELOPE_MARKER, 'value' => $value];
            }

            return $value;
        }

        return ['__formie_encrypted' => 1, 'ciphertext' => base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Formie::$plugin->getSettings()->getSecurityKey()))];
    }

    // Only trusted persisted-content hydration and field copying may call this method.
    public static function decode(mixed $value): mixed
    {
        if (self::_isPlainEnvelopeShape($value)) {
            return $value['value'];
        }

        if (self::_isEncryptedEnvelopeShape($value)) {
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
        if (($ciphertext = self::_legacyCiphertext($value)) !== null) {
            $plain = Craft::$app->getSecurity()->decryptByKey($ciphertext, Formie::$plugin->getSettings()->getSecurityKey());

            if ($plain === false) {
                throw new RuntimeException('Unable to decrypt the legacy Formie field value. Restore the original security key or database backup.');
            }

            return $plain;
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

    private static function _legacyCiphertext(mixed $value): ?string
    {
        if (!is_string($value) || !str_starts_with($value, 'base64:')) {
            return null;
        }

        $bytes = base64_decode(substr($value, 7), true);

        return $bytes !== false && str_starts_with($bytes, 'crypt:') ? substr($bytes, 6) : null;
    }

    private static function _isPlainEnvelopeShape(mixed $value): bool
    {
        return is_array($value) && count($value) === 2 && ($value['__formie_storage'] ?? null) === self::PLAIN_ENVELOPE_MARKER && array_key_exists('value', $value);
    }

    private static function _isEncryptedEnvelopeShape(mixed $value): bool
    {
        return is_array($value) && count($value) === 2 && array_key_exists('__formie_encrypted', $value) && array_key_exists('ciphertext', $value);
    }
}
