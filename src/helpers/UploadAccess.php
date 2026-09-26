<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

/** Durable, purpose-bound upload capabilities. Tokens are returned once; only hashes persist. */
final class UploadAccess
{
    // Static Methods
    // =========================================================================

    public static function issueToken(int $assetId, int $formId, string $fieldUid, string $purpose = 'view'): ?string
    {
        $mutex = Craft::$app->getMutex();
        $key = 'formie.upload-capability.' . $assetId;
        if (!$mutex->acquire($key, 5)) {
            throw new \RuntimeException('Upload capability is busy.');
        }
        try {
            $row = Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId($assetId, $formId, $fieldUid);
            if (!$row || !in_array($purpose, ['view', 'attach', 'delete'], true) || (int)$row['expiresAt'] <= time() || in_array($row['state'], ['expired', 'rejected'], true)) {
                return null;
            }
            $token = Craft::$app->getSecurity()->generateRandomString(64);
            $hashes = Json::decodeIfJson($row['capabilities']) ?: [];
            // Multiple renders/devices may retain valid capabilities until expiry or explicit revocation.
            $hashes[$purpose] = array_slice(array_merge($hashes[$purpose] ?? [], ['v1:' . hash('sha256', $token)]), -16);
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['capabilities' => Json::encode($hashes)], ['assetId' => $assetId])->execute();
            return $row['uid'] . '.' . $token;
        } finally {
            $mutex->release($key);
        }
    }

    public static function resolveToken(?string $token, string $purpose = 'view'): ?array
    {
        if (!$token || !str_contains($token, '.')) {
            return null;
        }
        [$uid, $secret] = explode('.', $token, 2);
        $row = (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where(['uid' => $uid])->one();
        if (!$row || (int)$row['expiresAt'] <= time() || in_array($row['state'], ['expired', 'rejected'], true)) {
            return null;
        }
        $hashes = Json::decodeIfJson($row['capabilities']) ?: [];
        foreach ($hashes[$purpose] ?? [] as $hash) {
            if (hash_equals($hash, 'v1:' . hash('sha256', $secret))) {
                return $row;
            }
        }
        return null;
    }

    public static function matches(int $assetId, int $formId, string $fieldUid, ?string $token, string $purpose = 'view'): bool
    {
        $row = self::resolveToken($token, $purpose);
        return $row && (int)$row['assetId'] === $assetId && (int)$row['formId'] === $formId && $row['fieldUid'] === $fieldUid;
    }
}
