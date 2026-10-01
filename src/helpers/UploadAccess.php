<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\enums\SubmissionUploadStatus;

use Craft;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

/** Durable, purpose-bound upload capabilities. Tokens are returned once; only hashes persist. */
final class UploadAccess
{
    // Constants
    // =========================================================================

    private const CREATE_TOKEN_TTL = 7200;


    // Static Methods
    // =========================================================================

    public static function viewUrl(?string $token): ?string
    {
        return $token ? UrlHelper::actionUrl('formie/file-upload/view', ['token' => $token]) : null;
    }

    public static function issueCreateToken(Form $form): string
    {
        return Craft::$app->getSecurity()->hashData(Json::encode([
            'version' => 1,
            'purpose' => 'upload.create',
            'formUid' => $form->uid,
            'siteId' => (int)$form->siteId,
            'browserHash' => Formie::$plugin->getSubmissionGrants()->browserHash($form),
            'issuedAt' => time(),
            'nonce' => Craft::$app->getSecurity()->generateRandomString(),
        ]));
    }

    public static function validateCreateToken(Form $form, ?string $token): bool
    {
        $payload = Craft::$app->getSecurity()->validateData(trim((string)$token));
        $data = $payload === false ? null : Json::decodeIfJson($payload);

        return is_array($data)
            && (int)($data['version'] ?? 0) === 1
            && ($data['purpose'] ?? null) === 'upload.create'
            && hash_equals((string)$form->uid, (string)($data['formUid'] ?? ''))
            && (int)($data['siteId'] ?? 0) === (int)$form->siteId
            && hash_equals(Formie::$plugin->getSubmissionGrants()->browserHash($form), (string)($data['browserHash'] ?? ''))
            && (int)($data['issuedAt'] ?? 0) <= time()
            && (int)($data['issuedAt'] ?? 0) > time() - self::CREATE_TOKEN_TTL;
    }

    public static function issueToken(int $assetId, int $formId, string $fieldUid, string $purpose = 'view'): ?string
    {
        $mutex = Craft::$app->getMutex();
        $key = 'formie.upload-capability.' . $assetId;

        if (!$mutex->acquire($key, 5)) {
            throw new \RuntimeException('Upload capability is busy.');
        }

        try {
            $row = Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId($assetId, $formId, $fieldUid);

            if (!$row || !in_array($purpose, ['view', 'attach', 'delete'], true) || (int)$row['expiresAt'] <= time() || in_array($row['state'], [SubmissionUploadStatus::EXPIRED->value, SubmissionUploadStatus::REJECTED->value], true)) {
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

        if (!$row || (int)$row['expiresAt'] <= time() || in_array($row['state'], [SubmissionUploadStatus::EXPIRED->value, SubmissionUploadStatus::REJECTED->value], true)) {
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
