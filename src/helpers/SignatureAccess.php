<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\ParentField;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Signature;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use Throwable;

final class SignatureAccess
{
    // Constants
    // =========================================================================

    private const TOKEN_PURPOSE = 'signature-image';
    private const TOKEN_VERSION = 2;
    private const LEGACY_TOKEN_PURPOSE = 'formie-signature-v1';


    // Properties
    // =========================================================================

    private static array $_accessStates = [];
    private static ?bool $_hasAccessColumns = null;


    // Public Methods
    // =========================================================================

    public static function issueAccessToken(Submission $submission, int $fieldId, string $fieldKey, mixed $value): ?string
    {
        $value = self::normalizeValue($value);

        if (!self::_hasValidContext($submission, $fieldId, $fieldKey) || $value === '') {
            return null;
        }

        $accessKey = self::_ensureAccessKey($submission);
        if (!$accessKey) {
            return null;
        }

        $payload = [
            'version' => self::TOKEN_VERSION,
            'purpose' => self::TOKEN_PURPOSE,
            'submissionUid' => $submission->uid,
            'formId' => (int)$submission->formId,
            'siteId' => (int)$submission->siteId,
            'fieldId' => $fieldId,
            'fieldKey' => $fieldKey,
            'valueHash' => hash('sha256', $value),
        ];
        $encodedPayload = self::_base64UrlEncode(Json::encode($payload));
        $signature = hash_hmac('sha256', $encodedPayload, $accessKey);

        return $encodedPayload . '.' . $signature;
    }

    public static function resolveAccessToken(string $accessToken, array $legacyContext = []): ?array
    {
        $accessToken = trim($accessToken);

        if ($accessToken === '' || strlen($accessToken) > 4096) {
            return null;
        }

        if (preg_match('/^[a-f0-9]{64}$/D', $accessToken)) {
            return self::_resolveLegacySignedToken($accessToken, $legacyContext);
        }

        $parts = explode('.', $accessToken);
        if (count($parts) !== 2 || !preg_match('/^[A-Za-z0-9_-]+$/D', $parts[0]) || !preg_match('/^[a-f0-9]{64}$/D', $parts[1])) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        $json = self::_base64UrlDecode($encodedPayload);

        if ($json === null) {
            return null;
        }

        try {
            $payload = Json::decode($json);
        } catch (Throwable) {
            return null;
        }

        if (!is_array($payload)
            || ($payload['version'] ?? null) !== self::TOKEN_VERSION
            || ($payload['purpose'] ?? null) !== self::TOKEN_PURPOSE) {
            return null;
        }

        $context = self::_normalizeContext($payload, true);
        if (!$context) {
            return null;
        }

        $submission = self::_findSubmission($context['submissionUid'], $context['formId'], $context['siteId']);
        if (!$submission) {
            return null;
        }

        $accessKey = self::_getAccessState($submission)['accessKey'] ?? null;
        if (!$accessKey || !hash_equals(hash_hmac('sha256', $encodedPayload, $accessKey), $signature)) {
            return null;
        }

        return self::_resolveSignedContext($submission, $context, true);
    }

    public static function resolveLegacyAccess(string $submissionUid, int $fieldId): ?array
    {
        $submissionUid = trim($submissionUid);

        if ($submissionUid === '' || $fieldId <= 0 || !Formie::$plugin->getSettings()->allowLegacySignatureImageUrls) {
            return null;
        }

        $submission = Submission::find()
            ->uid($submissionUid)
            ->isIncomplete(null)
            ->one();

        if (!$submission || !self::usesLegacyAccess($submission)) {
            return null;
        }

        $form = $submission->getForm();
        $field = null;

        foreach ($form?->getFields() ?? [] as $candidate) {
            if ((int)$candidate->id === $fieldId) {
                $field = $candidate;
                break;
            }
        }

        // Historical unsigned URLs never identified a nested value path. Keep
        // this compatibility branch limited to the exact top-level field.
        if (!$form || !$field instanceof Signature) {
            return null;
        }

        return self::_buildResolvedContext($submission, $form, $field, $field->valueKey());
    }

    public static function usesLegacyAccess(Submission $submission): bool
    {
        return (self::_getAccessState($submission)['legacy'] ?? false) === true;
    }

    public static function normalizeValue(mixed $value): string
    {
        return trim((string)$value);
    }


    // Private Methods
    // =========================================================================

    private static function _resolveLegacySignedToken(string $accessToken, array $legacyContext): ?array
    {
        $context = self::_normalizeContext($legacyContext, false);
        if (!$context) {
            return null;
        }

        $submission = self::_findSubmission($context['submissionUid'], null, $context['siteId']);
        if (!$submission) {
            return null;
        }

        $accessKey = self::_getAccessState($submission)['accessKey'] ?? null;
        if (!$accessKey) {
            return null;
        }

        $expected = hash_hmac('sha256', Json::encode([
            'purpose' => self::LEGACY_TOKEN_PURPOSE,
            'submissionUid' => $submission->uid,
            'formId' => (int)$submission->formId,
            'siteId' => (int)$submission->siteId,
            'fieldId' => $context['fieldId'],
            'fieldKey' => $context['fieldKey'],
        ]), $accessKey);

        if (!hash_equals($expected, $accessToken)) {
            return null;
        }

        $context['formId'] = (int)$submission->formId;

        return self::_resolveSignedContext($submission, $context, false);
    }

    private static function _resolveSignedContext(Submission $submission, array $context, bool $checkValueHash): ?array
    {
        $form = $submission->getForm();

        if (!$form || (int)$form->id !== $context['formId'] || (int)$submission->siteId !== $context['siteId']) {
            return null;
        }

        $field = self::_findSignatureField($form->getFields(), $context['fieldId'], $context['fieldKey']);
        if (!$field) {
            return null;
        }

        $resolved = self::_buildResolvedContext($submission, $form, $field, $context['fieldKey']);
        if (!$resolved) {
            return null;
        }

        if ($checkValueHash && !hash_equals($context['valueHash'], hash('sha256', $resolved['value']))) {
            return null;
        }

        return $resolved;
    }

    private static function _buildResolvedContext(Submission $submission, Form $form, Signature $field, string $fieldKey): ?array
    {
        $value = self::normalizeValue($submission->getFieldValue($fieldKey));

        if ($value === '') {
            return null;
        }

        return [
            'submission' => $submission,
            'form' => $form,
            'field' => $field,
            'fieldKey' => $fieldKey,
            'value' => $value,
        ];
    }

    private static function _findSignatureField(array $fields, int $fieldId, string $fieldKey): ?Signature
    {
        foreach ($fields as $field) {
            if ((int)$field->id === $fieldId) {
                return $field instanceof Signature && hash_equals($field->valueKey(), $fieldKey) ? $field : null;
            }

            if (!$field instanceof ParentField) {
                continue;
            }

            $rowKey = null;
            if ($field instanceof RepeatableParentFieldInterface) {
                $prefix = $field->valueKey() . '.';

                if (!str_starts_with($fieldKey, $prefix)) {
                    continue;
                }

                $remainder = substr($fieldKey, strlen($prefix));
                $rowKey = explode('.', $remainder, 2)[0] ?? '';

                if ($rowKey === '' || !ctype_digit($rowKey)) {
                    continue;
                }
            }

            $nested = self::_findSignatureField($field->getFields($rowKey), $fieldId, $fieldKey);
            if ($nested) {
                return $nested;
            }
        }

        return null;
    }

    private static function _normalizeContext(array $payload, bool $withValueHash): ?array
    {
        $submissionUid = isset($payload['submissionUid']) && is_string($payload['submissionUid']) ? trim($payload['submissionUid']) : '';
        $formId = isset($payload['formId']) ? (int)$payload['formId'] : 0;
        $siteId = isset($payload['siteId']) ? (int)$payload['siteId'] : 0;
        $fieldId = isset($payload['fieldId']) ? (int)$payload['fieldId'] : 0;
        $fieldKey = isset($payload['fieldKey']) && is_string($payload['fieldKey']) ? trim($payload['fieldKey']) : '';
        $valueHash = isset($payload['valueHash']) && is_string($payload['valueHash']) ? $payload['valueHash'] : '';

        if ($submissionUid === '' || strlen($submissionUid) > 255 || $siteId <= 0 || $fieldId <= 0
            || $fieldKey === '' || strlen($fieldKey) > 1024 || ($withValueHash && !preg_match('/^[a-f0-9]{64}$/D', $valueHash))) {
            return null;
        }

        if ($withValueHash && $formId <= 0) {
            return null;
        }

        return compact('submissionUid', 'formId', 'siteId', 'fieldId', 'fieldKey', 'valueHash');
    }

    private static function _findSubmission(string $submissionUid, ?int $formId, int $siteId): ?Submission
    {
        $query = Submission::find()
            ->uid($submissionUid)
            ->siteId($siteId)
            ->isIncomplete(null);

        if ($formId !== null) {
            $query->formId($formId);
        }

        return $query->one();
    }

    private static function _ensureAccessKey(Submission $submission): ?string
    {
        $state = self::_getAccessState($submission);
        if (!$state) {
            return null;
        }

        if ($state['accessKey']) {
            return $state['accessKey'];
        }

        $accessKey = Craft::$app->getSecurity()->generateRandomString(64);
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, [
            'signatureAccessKey' => $accessKey,
        ], [
            'and',
            ['id' => (int)$submission->id],
            ['or', ['signatureAccessKey' => null], ['signatureAccessKey' => '']],
        ])->execute();

        unset(self::$_accessStates[(int)$submission->id]);

        return self::_getAccessState($submission)['accessKey'] ?? null;
    }

    private static function _getAccessState(Submission $submission): ?array
    {
        $submissionId = (int)$submission->id;

        if ($submissionId <= 0 || !self::_hasRequiredColumns()) {
            return null;
        }

        if (!array_key_exists($submissionId, self::$_accessStates)) {
            // Notification URLs can be rendered immediately after persistence, so
            // always read the key and legacy marker from the database primary.
            $row = Craft::$app->getDb()->useMaster(fn() => (new Query())
                ->select(['signatureAccessKey', 'legacySignatureAccess'])
                ->from([Table::FORMIE_SUBMISSIONS])
                ->where(['id' => $submissionId])
                ->one());

            self::$_accessStates[$submissionId] = $row ? [
                'accessKey' => is_string($row['signatureAccessKey'] ?? null) && $row['signatureAccessKey'] !== '' ? $row['signatureAccessKey'] : null,
                'legacy' => (bool)($row['legacySignatureAccess'] ?? false),
            ] : null;
        }

        return self::$_accessStates[$submissionId];
    }

    private static function _hasRequiredColumns(): bool
    {
        if (self::$_hasAccessColumns === true) {
            return true;
        }

        // Long-running queue workers can load the new code before migrations run.
        // Recheck missing schema state until both columns become visible.
        $refresh = self::$_hasAccessColumns === false;
        $schema = Craft::$app->getDb()->getTableSchema(Table::FORMIE_SUBMISSIONS, $refresh);

        if (!$refresh && (!$schema?->getColumn('signatureAccessKey') || !$schema?->getColumn('legacySignatureAccess'))) {
            $schema = Craft::$app->getDb()->getTableSchema(Table::FORMIE_SUBMISSIONS, true);
        }

        self::$_hasAccessColumns = $schema?->getColumn('signatureAccessKey') !== null
            && $schema?->getColumn('legacySignatureAccess') !== null;

        return self::$_hasAccessColumns;
    }

    private static function _hasValidContext(Submission $submission, int $fieldId, string $fieldKey): bool
    {
        return is_string($submission->uid) && $submission->uid !== ''
            && (int)$submission->formId > 0
            && (int)$submission->siteId > 0
            && $fieldId > 0
            && $fieldKey !== '';
    }

    private static function _base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function _base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;

        if ($remainder === 1) {
            return null;
        }

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }
}
