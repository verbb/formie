<?php
namespace verbb\formie\content;

use verbb\formie\Formie;
use verbb\formie\exceptions\EncryptedContentUnavailableException;

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

        $key = ContentEncryption::currentKey();

        if (ContentEncryption::activeVersion() >= 2) {
            if (!$key) {
                throw new RuntimeException('Encrypted field content cannot be saved because FORMIE_ENCRYPTION_KEY is not configured.');
            }

            return [
                '__formie_enc' => 2,
                'k' => $key->id,
                'c' => ContentEncryption::encryptV2(Json::encode($value), $key),
            ];
        }

        // Existing Formie 4 installations remain on their readable v1 format
        // until the explicit activation command is run across every web/worker node.
        if (!$key) {
            Craft::$app->getDeprecator()->log(
                'formie.legacyFieldContentEncryption',
                'Encrypted fields are using FORMIE_SECURITY_KEY or Craft’s securityKey. Configure FORMIE_ENCRYPTION_KEY, activate v2 writes, then run formie/encryption/reencrypt.',
            );
        }

        return ['__formie_encrypted' => 1, 'ciphertext' => base64_encode(Craft::$app->getSecurity()->encryptByKey(Json::encode($value), Formie::$plugin->getSettings()->getSecurityKey()))];
    }

    // Only trusted persisted-content hydration and field copying may call this method.
    public static function decode(mixed $value): mixed
    {
        if (self::_isPlainEnvelopeShape($value)) {
            return $value['value'];
        }

        if (self::_isV2EnvelopeShape($value)) {
            if (!is_string($value['k']) || !is_string($value['c'])) {
                throw new EncryptedContentUnavailableException('Unsupported or malformed stored Formie encryption envelope.', null, $value);
            }

            $key = ContentEncryption::keysById()[$value['k']] ?? null;
            $plain = $key ? ContentEncryption::decryptV2($value['c'], $key) : false;

            if ($plain === false) {
                throw new EncryptedContentUnavailableException(
                    "Encrypted field content requires key {$value['k']}, which is not available in this environment.",
                    $value['k'],
                    $value,
                );
            }

            return Json::decode($plain);
        }

        if (self::_isV1EnvelopeShape($value)) {
            if (!is_string($value['ciphertext'])) {
                throw new EncryptedContentUnavailableException('Unsupported or malformed stored Formie encryption envelope.', null, $value);
            }

            $bytes = base64_decode($value['ciphertext'], true);
            $plain = self::_decryptLegacy($bytes);

            if ($plain === false) {
                throw new EncryptedContentUnavailableException('Unable to decrypt the stored Formie field value with the configured compatibility keys.', null, $value);
            }

            return Json::decode($plain);
        }

        // Formie 3's exact prefix and decoded marker, never a substring or a request heuristic.
        if (($ciphertext = self::_legacyCiphertext($value)) !== null) {
            $plain = self::_decryptLegacy($ciphertext);

            if ($plain === false) {
                throw new EncryptedContentUnavailableException('Unable to decrypt the legacy Formie field value with the configured compatibility keys.', null, $value);
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

    public static function containsEncryptedValue(mixed $value): bool
    {
        if (self::_isPlainEnvelopeShape($value)) {
            return false;
        }

        if (self::_legacyCiphertext($value) !== null || self::_isEncryptedEnvelopeShape($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (self::containsEncryptedValue($item)) {
                return true;
            }
        }

        return false;
    }

    public static function isProtectedPlaintext(mixed $value): bool
    {
        return self::_isPlainEnvelopeShape($value);
    }

    public static function isEncryptedValue(mixed $value): bool
    {
        return self::_legacyCiphertext($value) !== null || self::_isEncryptedEnvelopeShape($value);
    }

    public static function isCurrentEncryptedValue(mixed $value, string $targetKeyId): bool
    {
        return self::_isV2EnvelopeShape($value) && ($value['k'] ?? null) === $targetKeyId;
    }

    public static function requiresReencryption(mixed $value, string $targetKeyId): bool
    {
        if (self::_isPlainEnvelopeShape($value)) {
            return false;
        }

        if (self::_legacyCiphertext($value) !== null) {
            return true;
        }

        if (self::_isEncryptedEnvelopeShape($value)) {
            return self::_isV1EnvelopeShape($value) || ($value['k'] ?? null) !== $targetKeyId;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (self::requiresReencryption($item, $targetKeyId)) {
                return true;
            }
        }

        return false;
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
        return self::_isV1EnvelopeShape($value) || self::_isV2EnvelopeShape($value);
    }

    private static function _isV1EnvelopeShape(mixed $value): bool
    {
        return is_array($value)
            && count($value) === 2
            && ($value['__formie_encrypted'] ?? null) === 1
            && array_key_exists('ciphertext', $value);
    }

    private static function _isV2EnvelopeShape(mixed $value): bool
    {
        return is_array($value)
            && count($value) === 3
            && ($value['__formie_enc'] ?? null) === 2
            && array_key_exists('k', $value)
            && array_key_exists('c', $value);
    }

    private static function _decryptLegacy(string|false $ciphertext): string|false
    {
        if ($ciphertext === false) {
            return false;
        }

        foreach (ContentEncryption::legacyKeys() as $key) {
            $plain = Craft::$app->getSecurity()->decryptByKey($ciphertext, $key);

            if ($plain !== false) {
                return $plain;
            }
        }

        return false;
    }
}
