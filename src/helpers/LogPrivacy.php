<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;

use Throwable;

/** Keeps the shape of submitted data in Formie's logs while withholding the values themselves. */
final class LogPrivacy
{
    // Static Methods
    // =========================================================================

    public static function logsSubmissionValues(): bool
    {
        return (bool)(Formie::$plugin?->getSettings()->logSubmissionValues ?? false);
    }

    /**
     * Prepares request parameters for the log's request context. Log export runs late in the
     * request and must never throw, so any failure to inspect the form masks everything.
     */
    public static function maskRequestParams(array $params, ?string $formHandle): array
    {
        if (!$params) {
            return $params;
        }

        try {
            if (self::logsSubmissionValues()) {
                $form = $formHandle ? Formie::$plugin->getForms()->getFormByHandle($formHandle) : null;

                return self::_maskEncryptedParams($params, self::_encryptedFieldHandles($form));
            }
        } catch (Throwable) {
            // Fall through to masking every value.
        }

        foreach ($params as $key => $value) {
            if (!in_array($key, self::CONTROL_PARAMS, true)) {
                $params[$key] = self::maskValue($value);
            }
        }

        return $params;
    }

    public static function maskRequestBody(string $body): string
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? json_encode(self::maskValue($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : self::maskValue($body);
    }

    /**
     * Masks JSON within an integration log message when values shouldn't be logged. A mapped
     * payload no longer shows which field each value came from, so a form with any encrypted
     * field has its whole payload masked. Messages are matched by structure rather than wording
     * because their labels are translated.
     */
    public static function maskIntegrationMessage(string $message, ?int $formId): string
    {
        try {
            $mask = !self::logsSubmissionValues()
                || ($formId && self::_encryptedFieldHandles(Formie::$plugin->getForms()->getFormById($formId)));
        } catch (Throwable) {
            $mask = true;
        }

        return $mask ? self::_maskJsonInMessage($message) : $message;
    }

    public static function maskValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => array_map(self::maskValue(...), $value),
            $value === null, $value === '' => '<empty>',
            is_bool($value) => '<boolean>',
            is_int($value), is_float($value) => '<number>',
            is_string($value) => '<string:' . mb_strlen($value) . '>',
            default => '<value>',
        };
    }

    private static function _encryptedFieldHandles(?Form $form): array
    {
        $handles = [];

        foreach ($form?->getFieldsRecursively() ?? [] as $field) {
            if (!empty($field->enableContentEncryption)) {
                $handles[$field->handle] = true;
            }
        }

        return $handles;
    }

    private static function _maskEncryptedParams(array $params, array $handles): array
    {
        if (!$handles) {
            return $params;
        }

        foreach ($params as $key => $value) {
            if (isset($handles[$key])) {
                $params[$key] = '<encrypted>';
            } elseif (is_array($value)) {
                $params[$key] = self::_maskEncryptedParams($value, $handles);
            }
        }

        return $params;
    }

    private static function _maskJsonInMessage(string $message): string
    {
        $offset = 0;

        while (preg_match('/[{\[]/', $message, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $match[0][1];
            $end = self::_findJsonEnd($message, $start);

            if ($end === null) {
                // Long provider responses are cut off mid-document, leaving JSON that can't be parsed.
                if (preg_match('/\G[{\[]\s*["{\[]/', $message, $partial, 0, $start)) {
                    return substr($message, 0, $start) . '<truncated>';
                }

                $offset = $start + 1;

                continue;
            }

            $decoded = json_decode(substr($message, $start, $end - $start + 1), true);

            if (!is_array($decoded)) {
                $offset = $start + 1;

                continue;
            }

            $masked = json_encode(self::maskValue($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $message = substr_replace($message, $masked, $start, $end - $start + 1);
            $offset = $start + strlen($masked);
        }

        return $message;
    }

    private static function _findJsonEnd(string $message, int $start): ?int
    {
        $depth = 0;
        $inString = false;
        $length = strlen($message);

        for ($i = $start; $i < $length; $i++) {
            $char = $message[$i];

            if ($inString) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{' || $char === '[') {
                $depth++;
            } elseif (($char === '}' || $char === ']') && --$depth === 0) {
                return $i;
            }
        }

        return null;
    }


    // Constants
    // =========================================================================

    // Request routing rather than anything a visitor entered, and needed to follow a support request.
    private const CONTROL_PARAMS = [
        'action',
        'expectedVersion',
        'formId',
        'formStartedAt',
        'formieUploadPayloadVersion',
        'handle',
        'mode',
        'p',
        'pageId',
        'siteId',
        'submissionId',
        'submitAction',
    ];
}
