<?php
namespace verbb\formie\compatibility\signatures;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Signature;

use craft\helpers\Json;

trait SignatureAccessCompatibility
{
    // Static Methods
    // =========================================================================

    public static function resolveLegacySignedToken(string $accessToken, array $legacyContext): ?array
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


    // Constants
    // =========================================================================

    private const LEGACY_TOKEN_PURPOSE = 'formie-signature-v1';
}
