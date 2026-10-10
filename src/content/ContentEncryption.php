<?php
namespace verbb\formie\content;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;

use RuntimeException;
use yii\base\Security;

final class ContentEncryption
{
    // Constants
    // =========================================================================

    public const KEY_PREFIX = 'formie-root:v1:';


    // Properties
    // =========================================================================

    private static ?Security $_security = null;


    // Static Methods
    // =========================================================================

    public static function currentKey(): ?ContentEncryptionKey
    {
        $configured = App::env('FORMIE_ENCRYPTION_KEY');

        if ($configured === null || $configured === '') {
            $configured = Formie::$plugin->getSettings()->encryptionKey;
        }

        if ($configured === null || $configured === '') {
            return null;
        }

        if (!is_string($configured)) {
            throw new RuntimeException('FORMIE_ENCRYPTION_KEY must be a string.');
        }

        $configured = App::parseEnv($configured);

        if (!is_string($configured)) {
            throw new RuntimeException('FORMIE_ENCRYPTION_KEY must resolve to a string.');
        }

        return self::parseKey($configured);
    }

    public static function parseKey(string $value): ContentEncryptionKey
    {
        if (!str_starts_with($value, self::KEY_PREFIX)) {
            throw new RuntimeException('FORMIE_ENCRYPTION_KEY is malformed. Generate a Formie encryption key before enabling encrypted field writes.');
        }

        $encoded = substr($value, strlen(self::KEY_PREFIX));

        if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $encoded)) {
            throw new RuntimeException('FORMIE_ENCRYPTION_KEY is malformed. Generate a Formie encryption key before enabling encrypted field writes.');
        }

        $bytes = base64_decode(strtr($encoded, '-_', '+/') . '=', true);

        if ($bytes === false || strlen($bytes) !== 32) {
            throw new RuntimeException('FORMIE_ENCRYPTION_KEY is malformed. Generate a Formie encryption key before enabling encrypted field writes.');
        }

        $id = 'r_' . substr(hash_hmac('sha256', 'formie/key-id/v1', $bytes), 0, 12);

        return new ContentEncryptionKey($value, $bytes, $id);
    }

    public static function generateKey(): ContentEncryptionKey
    {
        $bytes = random_bytes(32);
        $value = self::KEY_PREFIX . rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return self::parseKey($value);
    }

    public static function keysById(): array
    {
        $keys = [];

        if ($current = self::currentKey()) {
            $keys[$current->id] = $current;
        }

        foreach (self::_previousKeyValues() as $value) {
            try {
                $key = self::parseKey($value);
                $keys[$key->id] = $key;
            } catch (RuntimeException) {
                // Legacy Formie and Craft keys are valid rescue material for old
                // formats, but cannot identify or decrypt a v2 envelope.
            }
        }

        return $keys;
    }

    public static function legacyKeys(): array
    {
        $keys = [];
        $formieKey = App::env('FORMIE_SECURITY_KEY');

        if (is_string($formieKey) && $formieKey !== '') {
            $keys[] = $formieKey;
        }

        $craftKey = Craft::$app->getConfig()->getGeneral()->securityKey;

        if (is_string($craftKey) && $craftKey !== '') {
            $keys[] = $craftKey;
        }

        foreach (self::_previousKeyValues() as $value) {
            // Legacy formats used the configured string itself as key material.
            // A value that resembles a v2 root key must therefore stay intact.
            $keys[] = $value;
        }

        return array_values(array_unique($keys));
    }

    public static function encryptV2(string $plain, ContentEncryptionKey $key): string
    {
        return base64_encode(self::_security()->encryptByKey($plain, $key->bytes, 'formie/field-content/v2'));
    }

    public static function decryptV2(string $ciphertext, ContentEncryptionKey $key): string|false
    {
        $bytes = base64_decode($ciphertext, true);

        return $bytes === false ? false : self::_security()->decryptByKey($bytes, $key->bytes, 'formie/field-content/v2');
    }

    public static function activeVersion(): int
    {
        if (!self::_stateAvailable()) {
            return 1;
        }

        return (int)((new Query())
            ->select('contentVersion')
            ->from(Table::FORMIE_ENCRYPTION_SETTINGS)
            ->where(['id' => 1])
            ->scalar() ?: 1);
    }

    public static function expectedKeyId(): ?string
    {
        if (!self::_stateAvailable()) {
            return null;
        }

        $value = (new Query())
            ->select('expectedKeyId')
            ->from(Table::FORMIE_ENCRYPTION_SETTINGS)
            ->where(['id' => 1])
            ->scalar();

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function reencryptCursor(string $keyId): int
    {
        if (!self::_stateAvailable()) {
            return 0;
        }

        $row = (new Query())
            ->select(['reencryptCursor', 'reencryptTargetKeyId'])
            ->from(Table::FORMIE_ENCRYPTION_SETTINGS)
            ->where(['id' => 1])
            ->one();

        return ($row['reencryptTargetKeyId'] ?? null) === $keyId ? (int)($row['reencryptCursor'] ?? 0) : 0;
    }

    public static function setReencryptCursor(string $keyId, int $cursor): void
    {
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_ENCRYPTION_SETTINGS, [
            'reencryptCursor' => $cursor,
            'reencryptTargetKeyId' => $keyId,
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => 1])->execute();
    }

    public static function completeReencryption(string $keyId): void
    {
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_ENCRYPTION_SETTINGS, [
            'expectedKeyId' => $keyId,
            'reencryptCursor' => null,
            'reencryptTargetKeyId' => null,
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => 1])->execute();
    }


    // Private Methods
    // =========================================================================

    private static function _previousKeyValues(): array
    {
        $configured = Formie::$plugin->getSettings()->encryptionPreviousKeys;
        $environment = App::env('FORMIE_ENCRYPTION_PREVIOUS_KEYS');

        if (is_array($environment)) {
            $decoded = $environment;
            $configured = [...$configured, ...$decoded];
        } elseif (is_string($environment) && trim($environment) !== '') {
            if (str_starts_with(trim($environment), '[')) {
                try {
                    $decoded = Json::decode($environment);
                } catch (\Throwable) {
                    throw new RuntimeException('FORMIE_ENCRYPTION_PREVIOUS_KEYS must be a comma-separated list or JSON array of key strings.');
                }

                if (!is_array($decoded)) {
                    throw new RuntimeException('FORMIE_ENCRYPTION_PREVIOUS_KEYS must be a comma-separated list or JSON array of key strings.');
                }
            } else {
                $decoded = array_map('trim', explode(',', $environment));
            }

            $configured = [...$configured, ...$decoded];
        } elseif ($environment !== null && $environment !== '') {
            throw new RuntimeException('FORMIE_ENCRYPTION_PREVIOUS_KEYS must be a comma-separated list or JSON array of key strings.');
        }

        foreach ($configured as &$value) {
            $value = is_string($value) ? App::parseEnv($value) : $value;

            if (!is_string($value) || $value === '') {
                throw new RuntimeException('Every previous Formie encryption key must be a non-empty string.');
            }
        }
        unset($value);

        return array_values(array_unique($configured));
    }

    private static function _stateAvailable(): bool
    {
        return Craft::$app->getDb()->getIsActive()
            && Formie::$plugin->isInstalled
            && !Craft::$app->getPlugins()->isPluginUpdatePending(Formie::$plugin);
    }

    private static function _security(): Security
    {
        return self::$_security ??= new Security([
            'cipher' => 'AES-256-CBC',
            'kdfHash' => 'sha256',
            'macHash' => 'sha256',
            'authKeyInfo' => 'formie/field-content/auth/v2',
        ]);
    }
}
