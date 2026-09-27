<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Summary;
use verbb\formie\models\ResolvedTheme;
use verbb\formie\services\SubmissionOperations;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use RuntimeException;

final class FieldAccess
{
    // Static Methods
    // =========================================================================

    public static function issueAccessToken(Submission $submission, int $fieldId): ?string
    {
        $submissionUid = trim((string)($submission->uid ?? ''));
        $formId = (int)($submission->formId ?? 0);

        if ($submissionUid === '' || $formId <= 0 || $fieldId <= 0) {
            return null;
        }

        $form = $submission->getForm();
        if (!$form) {
            return null;
        }
        $isSummary = $form->getFieldById($fieldId) instanceof Summary;
        $payload = [
            'version' => 2,
            'purpose' => $isSummary ? 'summary' : 'field',
            'submissionUid' => $submissionUid,
            'formId' => $formId,
            'siteId' => (int)$form->siteId,
            'fieldId' => $fieldId,
        ];
        // Signature image links do not render a theme and retain their existing
        // lifetime independently of expiring Summary fragment state.
        if ($isSummary) {
            $frame = Formie::$plugin->getRendering()->getActiveRenderFrame();
            $resolvedTheme = ($frame && (int)$frame->getForm()->id === $formId && (int)$frame->getForm()->siteId === (int)$form->siteId)
                ? $frame->getResolvedTheme()
                : Formie::$plugin->getThemeConfigService()->resolve($form);
            $expiresAt = time() + SubmissionOperations::RETENTION_SECONDS;
            $payload += [
                'expiresAt' => $expiresAt,
                'theme' => self::_storeTheme($form, $resolvedTheme, $expiresAt),
                'themeMode' => $resolvedTheme->mode,
                'themeDigest' => $resolvedTheme->digest,
            ];
        }

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $encrypted = Craft::$app->getSecurity()->encryptByKey(Json::encode($payload), $key);

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        return base64_encode($encrypted);
    }

    public static function resolveAccessToken(?string $token): ?array
    {
        if (!is_string($token) || trim($token) === '' || strlen($token) > 2048) {
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

        if (!is_array($payload) || ($payload['version'] ?? null) !== 2 || !in_array($payload['purpose'] ?? null, ['summary', 'field'], true)) {
            return null;
        }

        $submissionUid = isset($payload['submissionUid']) && is_string($payload['submissionUid']) ? trim($payload['submissionUid']) : '';
        $formId = isset($payload['formId']) ? (int)$payload['formId'] : 0;
        $siteId = isset($payload['siteId']) ? (int)$payload['siteId'] : 0;
        $fieldId = isset($payload['fieldId']) ? (int)$payload['fieldId'] : 0;

        if ($submissionUid === '' || $formId <= 0 || $siteId <= 0 || $fieldId <= 0) {
            return null;
        }
        $context = ['submissionUid' => $submissionUid, 'formId' => $formId, 'siteId' => $siteId, 'fieldId' => $fieldId];
        if ($payload['purpose'] === 'field') {
            return $context + ['theme' => null];
        }
        if (!is_int($payload['expiresAt'] ?? null) || $payload['expiresAt'] <= time()
            || !array_key_exists('theme', $payload) || ($payload['theme'] !== null && !is_string($payload['theme']))
            || !in_array($payload['themeMode'] ?? null, ['formie', 'none'], true) || !is_string($payload['themeDigest'] ?? null)) {
            return null;
        }

        $theme = $payload['theme'] === null
            ? ['mode' => $payload['themeMode'], 'config' => [], 'digest' => $payload['themeDigest'], 'allowsRawHtml' => false]
            : self::_loadTheme($payload['theme'], $payload['themeDigest'], $formId, $siteId);
        if ($theme === null) {
            return null;
        }

        return $context + ['theme' => $theme];
    }

    private static function _storeTheme(Form $form, ResolvedTheme $theme, int $expiresAt): ?string
    {
        // Empty configuration needs only the token's mode/digest, not a database row.
        if ($theme->config === []) {
            return null;
        }

        // Reuse the shared, expiring instance store rather than a browser session or
        // node-local cache. Identical themes share one row even across Summary fields.
        $identity = hash('sha256', Json::encode(['summary-theme', $form->id, $form->siteId, $theme->digest, $theme->allowsRawHtml]));
        $state = Json::encode(['purpose' => 'summary-theme', 'theme' => $theme->toFragmentState()]);
        $encrypted = Craft::$app->getSecurity()->encryptByKey($state, Formie::$plugin->getSettings()->getSecurityKey());
        if (!is_string($encrypted)) {
            throw new RuntimeException('Unable to encrypt Summary theme state.');
        }
        Craft::$app->getDb()->createCommand()->upsert('{{%formie_instance_configs}}', [
            'tokenHash' => $identity, 'formId' => $form->id, 'siteId' => $form->siteId,
            'config' => base64_encode($encrypted), 'expiresAt' => $expiresAt,
        ], ['expiresAt' => $expiresAt])->execute();

        return $identity;
    }

    private static function _loadTheme(string $identity, string $digest, int $formId, int $siteId): ?array
    {
        $row = Craft::$app->getDb()->useMaster(fn() => (new Query())->from('{{%formie_instance_configs}}')->where([
            'tokenHash' => $identity, 'formId' => $formId, 'siteId' => $siteId,
        ])->andWhere(['>', 'expiresAt', time()])->one());
        if (!$row) {
            return null;
        }

        $bytes = base64_decode($row['config'], true);
        $plain = $bytes === false ? false : Craft::$app->getSecurity()->decryptByKey($bytes, Formie::$plugin->getSettings()->getSecurityKey());
        $state = $plain === false ? null : Json::decodeIfJson($plain);
        $theme = is_array($state) ? ($state['theme'] ?? null) : null;
        if (!is_array($state) || ($state['purpose'] ?? null) !== 'summary-theme' || !is_array($theme)
            || !is_string($theme['digest'] ?? null) || !hash_equals($digest, $theme['digest'])) {
            return null;
        }

        return $theme;
    }
}
