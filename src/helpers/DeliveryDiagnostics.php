<?php
namespace verbb\formie\helpers;

use Throwable;

/** Bounded support projections. Exact operational data belongs in encrypted storage. */
final class DeliveryDiagnostics
{
    // Static Methods
    // =========================================================================

    public static function exception(Throwable $error): array
    {
        $chain = [];

        do {
            $chain[] = [
                'type' => get_class($error),
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                // Never retain trace arguments: they can contain credentials or whole service objects.
                'trace' => array_map(static fn(array $frame): array => array_intersect_key($frame, array_flip(['file', 'line', 'class', 'type', 'function'])), array_slice($error->getTrace(), 0, 50)),
                'traceTruncated' => count($error->getTrace()) > 50,
            ];
            $error = $error->getPrevious();
        } while ($error && count($chain) < 5);

        return ['exceptions' => $chain, 'chainTruncated' => $error !== null];
    }

    public static function redact(mixed $value, array $secrets = [], int $depth = 0): mixed
    {
        if ($depth > 8) {
            return '[depth limit]';
        }

        if (is_array($value)) {
            $safe = [];

            foreach (array_slice($value, 0, 100, true) as $key => $item) {
                if (preg_match('/password|secret|token|authorization|cookie|api.?key|httpAuth|credential|card.?number|cvv/i', (string)$key)) {
                    $safe[$key] = '[redacted]';
                } else {
                    $safe[$key] = self::redact($item, $secrets, $depth + 1);
                }
            }
            return $safe;
        }

        if (is_string($value)) {
            if (strlen($value) <= 65536 && ($value[0] ?? '') === '{') {
                $decoded = json_decode($value, true);

                if (is_array($decoded)) {
                    return json_encode(self::redact($decoded, $secrets, $depth + 1), JSON_INVALID_UTF8_SUBSTITUTE);
                }
            }
            return mb_substr(self::_redactString($value, $secrets), 0, 2048);
        }
        return is_scalar($value) || $value === null ? $value : '[unsupported value]';
    }

    public static function redactComplete(mixed $value, array $secrets = [], int $depth = 0): mixed
    {
        if ($depth > 32) {
            return '[depth limit]';
        }

        if (is_array($value)) {
            $safe = [];

            foreach ($value as $key => $item) {
                if (preg_match('/password|secret|token|authorization|cookie|api.?key|httpAuth|credential|card.?number|cvv/i', (string)$key)) {
                    $safe[$key] = '[redacted]';
                } else {
                    $safe[$key] = self::redactComplete($item, $secrets, $depth + 1);
                }
            }
            return $safe;
        }

        if (is_string($value)) {
            if (strlen($value) <= 2097152 && in_array($value[0] ?? '', ['{', '['], true)) {
                $decoded = json_decode($value, true);

                if (is_array($decoded)) {
                    return json_encode(self::redactComplete($decoded, $secrets, $depth + 1), JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
                }
            }
            return self::_redactString($value, $secrets);
        }
        return is_scalar($value) || $value === null ? $value : '[unsupported value]';
    }

    public static function encode(mixed $value, array $secrets = []): string
    {
        $json = json_encode(self::redact($value, $secrets), JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if (strlen($json) > 16384) {
            return json_encode(['truncated' => true, 'preview' => mb_strcut($json, 0, 8192)], JSON_INVALID_UTF8_SUBSTITUTE);
        }
        return $json;
    }


    // Private Methods
    // =========================================================================

    private static function _redactString(string $value, array $secrets): string
    {
        foreach ($secrets as $secret) {
            if (is_string($secret) && $secret !== '') {
                $value = str_replace($secret, '[redacted]', $value);
            }
        }
        $value = preg_replace('/((?:password|secret|api[_-]?key|access[_-]?token)\s*[:=]\s*)[^\s,;]+/i', '$1[redacted]', $value);
        $value = preg_replace('/\b(Bearer|Basic)\s+[^\s"<>]+/i', '$1 [redacted]', $value);

        return preg_replace('/([?&](?:[^=&]*(?:token|secret|key|password)[^=&]*)=)[^&#\s]*/i', '$1[redacted]', $value) ?? $value;
    }
}
