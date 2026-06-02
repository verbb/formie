<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\Json;

final class FieldAccess
{
    public static function issueAccessToken(Submission $submission, int $fieldId): ?string
    {
        $submissionUid = trim((string)($submission->uid ?? ''));
        $formId = (int)($submission->formId ?? 0);

        if ($submissionUid === '' || $formId <= 0 || $fieldId <= 0) {
            return null;
        }

        $payload = Json::encode([
            'submissionUid' => $submissionUid,
            'formId' => $formId,
            'fieldId' => $fieldId,
        ]);

        $key = Formie::$plugin->getSettings()->getSecurityKey();
        $encrypted = Craft::$app->getSecurity()->encryptByKey($payload, $key);

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        return base64_encode($encrypted);
    }

    public static function resolveAccessToken(?string $token): ?array
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

        $submissionUid = isset($payload['submissionUid']) && is_string($payload['submissionUid']) ? trim($payload['submissionUid']) : '';
        $formId = isset($payload['formId']) ? (int)$payload['formId'] : 0;
        $fieldId = isset($payload['fieldId']) ? (int)$payload['fieldId'] : 0;

        if ($submissionUid === '' || $formId <= 0 || $fieldId <= 0) {
            return null;
        }

        return [
            'submissionUid' => $submissionUid,
            'formId' => $formId,
            'fieldId' => $fieldId,
        ];
    }
}
