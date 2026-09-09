<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\helpers\Json;

/**
 * Issues unguessable upload capabilities bound to asset + form + field.
 * Numeric asset IDs alone must not authorize hydrate/delete for guests.
 */
final class UploadAccess
{
    // Constants
    // =========================================================================

    private const MIN_TTL_SECONDS = 86400;
    private const MAX_TTL_SECONDS = 7776000;


    // Static Methods
    // =========================================================================

    public static function issueToken(int $assetId, int $formId, string $fieldUid, ?int $issuedAt = null): ?string
    {
        $fieldUid = trim($fieldUid);

        if ($assetId <= 0 || $formId <= 0 || $fieldUid === '') {
            return null;
        }

        $issuedAt ??= time();
        $payload = Json::encode([
            'assetId' => $assetId,
            'formId' => $formId,
            'fieldUid' => $fieldUid,
            'issuedAt' => $issuedAt,
            'expiresAt' => $issuedAt + self::_tokenTtlSeconds(),
        ]);

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $encrypted = Craft::$app->getSecurity()->encryptByKey($payload, $key);

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        return base64_encode($encrypted);
    }

    public static function resolveToken(?string $token): ?array
    {
        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $decoded = base64_decode(trim($token), true);

        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $decrypted = Craft::$app->getSecurity()->decryptByKey($decoded, $key);

        if (!is_string($decrypted) || $decrypted === '') {
            return null;
        }

        $payload = Json::decodeIfJson($decrypted);

        if (!is_array($payload)) {
            return null;
        }

        $assetId = isset($payload['assetId']) ? (int)$payload['assetId'] : 0;
        $formId = isset($payload['formId']) ? (int)$payload['formId'] : 0;
        $fieldUid = isset($payload['fieldUid']) && is_string($payload['fieldUid']) ? trim($payload['fieldUid']) : '';
        $expiresAt = isset($payload['expiresAt']) ? (int)$payload['expiresAt'] : 0;

        if ($assetId <= 0 || $formId <= 0 || $fieldUid === '' || $expiresAt <= time()) {
            return null;
        }

        return [
            'assetId' => $assetId,
            'formId' => $formId,
            'fieldUid' => $fieldUid,
            'expiresAt' => $expiresAt,
        ];
    }

    public static function matches(int $assetId, int $formId, string $fieldUid, ?string $token): bool
    {
        $resolved = self::resolveToken($token);

        if (!$resolved) {
            return false;
        }

        return $resolved['assetId'] === $assetId
            && $resolved['formId'] === $formId
            && $resolved['fieldUid'] === trim($fieldUid);
    }

    private static function _tokenTtlSeconds(): int
    {
        $days = (int)Formie::$plugin->getSettings()->maxIncompleteSubmissionAge;

        if ($days <= 0) {
            $days = 30;
        }

        return max(self::MIN_TTL_SECONDS, min(self::MAX_TTL_SECONDS, $days * 86400));
    }
}
