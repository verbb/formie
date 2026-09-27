<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Submission;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

final class SignatureAccess
{
    // Constants
    // =========================================================================

    private const TOKEN_PURPOSE = 'formie-signature-v1';


    // Properties
    // =========================================================================

    private static array $_accessKeys = [];
    private static ?bool $_hasAccessKeyColumn = null;


    // Public Methods
    // =========================================================================

    public static function issueAccessToken(Submission $submission, int $fieldId, string $fieldKey): ?string
    {
        $accessKey = self::_getAccessKey($submission);

        if (!$accessKey || !self::_hasValidContext($submission, $fieldId, $fieldKey)) {
            return null;
        }

        return self::_buildAccessToken($accessKey, $submission, $fieldId, $fieldKey);
    }

    public static function requiresAccessToken(Submission $submission): bool
    {
        return self::_getAccessKey($submission) !== null;
    }

    public static function validateAccessToken(Submission $submission, int $fieldId, string $fieldKey, string $accessToken): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $accessToken) || !self::_hasValidContext($submission, $fieldId, $fieldKey)) {
            return false;
        }

        $accessKey = self::_getAccessKey($submission);

        if (!$accessKey) {
            return false;
        }

        return hash_equals(self::_buildAccessToken($accessKey, $submission, $fieldId, $fieldKey), $accessToken);
    }


    // Private Methods
    // =========================================================================

    private static function _buildAccessToken(string $accessKey, Submission $submission, int $fieldId, string $fieldKey): string
    {
        return hash_hmac('sha256', Json::encode([
            'purpose' => self::TOKEN_PURPOSE,
            'submissionUid' => $submission->uid,
            'formId' => (int)$submission->formId,
            'siteId' => (int)$submission->siteId,
            'fieldId' => $fieldId,
            'fieldKey' => $fieldKey,
        ]), $accessKey);
    }

    private static function _getAccessKey(Submission $submission): ?string
    {
        $submissionId = (int)$submission->id;

        if ($submissionId <= 0 || !self::_hasAccessKeyColumn()) {
            return null;
        }

        if (!array_key_exists($submissionId, self::$_accessKeys)) {
            // A token can be rendered immediately after save, before a read replica has the new key.
            $accessKey = Craft::$app->getDb()->useMaster(fn() => (new Query())
                ->select(['signatureAccessKey'])
                ->from([Table::FORMIE_SUBMISSIONS])
                ->where(['id' => $submissionId])
                ->scalar());

            self::$_accessKeys[$submissionId] = is_string($accessKey) && $accessKey !== '' ? $accessKey : null;
        }

        return self::$_accessKeys[$submissionId];
    }

    private static function _hasAccessKeyColumn(): bool
    {
        if (self::$_hasAccessKeyColumn === true) {
            return true;
        }

        // Long-running queue workers can load the new code before the migration is applied.
        // Recheck a missing column so those workers adopt protected URLs after the schema changes.
        $refresh = self::$_hasAccessKeyColumn === false;
        $tableSchema = Craft::$app->getDb()->getTableSchema(Table::FORMIE_SUBMISSIONS, $refresh);

        if (!$refresh && $tableSchema?->getColumn('signatureAccessKey') === null) {
            $tableSchema = Craft::$app->getDb()->getTableSchema(Table::FORMIE_SUBMISSIONS, true);
        }

        self::$_hasAccessKeyColumn = $tableSchema?->getColumn('signatureAccessKey') !== null;

        return self::$_hasAccessKeyColumn;
    }

    private static function _hasValidContext(Submission $submission, int $fieldId, string $fieldKey): bool
    {
        return is_string($submission->uid) && $submission->uid !== ''
            && (int)$submission->formId > 0
            && (int)$submission->siteId > 0
            && $fieldId > 0
            && $fieldKey !== '';
    }
}
