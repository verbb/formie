<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Submission;
use verbb\formie\models\ReferenceExpression;
use verbb\formie\models\Notification;

class References
{
    /**
     * Resolve a single token-like value (e.g. "{field:abc}" or "{user:email|Guest}") to a value.
     * Non-token strings are returned unchanged.
     *
     * Supported options:
     * - variables: pre-built variable map from Variables::getVariablesForSubmission()
     * - includeSummary: whether to include expensive summary variables (allFields, etc.)
     * - notification: optional notification context used when building variables
     * - parseEnvValues: whether string variable values should resolve env aliases
     */
    public static function parseValue(mixed $value, Submission $submission, array $options = []): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (!self::_looksLikeReferenceToken($value)) {
            return $value;
        }

        $variables = $options['variables'] ?? null;

        if (!is_array($variables)) {
            $notification = $options['notification'] ?? null;
            $includeSummary = (bool)($options['includeSummary'] ?? false);
            $variables = Variables::getVariablesForSubmission(
                $submission,
                $notification instanceof Notification ? $notification : null,
                $includeSummary,
                (bool)($options['parseEnvValues'] ?? true)
            );
        }

        $resolved = Variables::getFieldAndValueForReference($value, $submission, $variables);

        return $resolved['value'];
    }

    /**
     * Parse a content string and interpolate any embedded {target:...} references.
     * Unknown/unresolved references become empty strings unless they provide an inline default.
     *
     * Supported options:
     * - variables: pre-built variable map from Variables::getVariablesForSubmission()
     * - includeSummary: whether to include expensive summary variables (allFields, etc.)
     * - notification: optional notification context used when building variables
     * - parseEnvValues: whether string variable values should resolve env aliases
     */
    public static function parseContent(string $content, Submission $submission, array $options = []): string
    {
        if (!str_contains($content, '{')) {
            return $content;
        }

        $variables = $options['variables'] ?? null;

        if (!is_array($variables)) {
            $notification = $options['notification'] ?? null;
            $includeSummary = (bool)($options['includeSummary'] ?? false);
            $variables = Variables::getVariablesForSubmission(
                $submission,
                $notification instanceof Notification ? $notification : null,
                $includeSummary,
                (bool)($options['parseEnvValues'] ?? true)
            );
        }

        return (string)preg_replace_callback('/\{[^{}]+\}/', function (array $matches) use ($submission, $variables) {
            $raw = (string)($matches[0] ?? '');
            $expr = self::parseReferenceExpression($raw);

            // Keep non-reference brace content unchanged.
            if (!$expr->isValid && $expr->target === '' && $expr->identifier === '') {
                return $raw;
            }

            $resolved = self::parseValue($raw, $submission, ['variables' => $variables]);

            return self::_stringifyListValue($resolved);
        }, $content);
    }

    /**
     * Parses a reference token, e.g. {field:ref:selector}, {user:firstName|Guest}, {timestamp}, {allFields}.
     * The first pipe (|) in the body is treated as the default separator: {target:body|default}.
     * Body is optional for some targets (e.g. {timestamp}, {allFields}).
     */
    public static function parseReferenceExpression(string $raw): ReferenceExpression
    {
        $expression = new ReferenceExpression([
            'raw' => $raw,
        ]);

        // Allow optional body: {target} or {target:body}. Body-less tokens can
        // still carry metadata, e.g. {timestamp;transform=format}.
        $bodylessMetadata = false;

        if (preg_match('/^\{([a-zA-Z]+)(;.*)\}$/', $raw, $matches)) {
            $target = trim($matches[1] ?? '');
            $body = $target . trim($matches[2] ?? '');
            $bodylessMetadata = true;
        } else if (preg_match('/^\{([a-zA-Z]+)(?::(.*))?\}$/', $raw, $matches)) {
            $target = trim($matches[1] ?? '');
            $body = trim($matches[2] ?? '');
        } else {
            return $expression;
        }

        // Optional inline default: first | separates body from default
        $default = '';
        if (str_contains($body, '|')) {
            [$body, $default] = explode('|', $body, 2);
            $body = trim($body);
            $default = trim($default);
        }

        // Optional transform metadata in body:
        // {target:body;transform=id;param=value}
        [$body, $transformerId, $transformerParams] = self::_extractTransformMetadata($body);

        if ($bodylessMetadata && $body === $target) {
            $body = '';
        }

        // Special case: body-less tokens.
        if (in_array($target, ['timestamp', 'allFields', 'allContentFields', 'allVisibleFields'], true) && $body === '') {
            $expression->default = $default;
            $expression->transformerId = $transformerId;
            $expression->transformerParams = $transformerParams;
            $expression->target = $target;
            $expression->identifier = '';
            $expression->isValid = true;

            return $expression;
        }

        if ($target === '' || $body === '') {
            return $expression;
        }

        $expression->default = $default;
        $expression->transformerId = $transformerId;
        $expression->transformerParams = $transformerParams;
        $expression->target = $target;
        $expression->identifier = $body;
        $expression->selector = '';
        $expression->isValid = true;

        if ($target === 'field') {
            $selector = '';

            if (str_contains($body, ':')) {
                [$expression->identifier, $selector] = explode(':', $body, 2);
                $expression->identifier = trim($expression->identifier);
                $expression->selector = trim($selector);
            }

            $expression->isValid = $expression->identifier !== '';

            return $expression;
        }

        if ($target === 'submission') {
            $expression->identifier = trim($body);
        }

        return $expression;
    }

    /**
     * Builds a token with an optional default: {target:body|default} or {target:body}.
     */
    public static function withDefault(string $tokenWithoutDefault, string $default): string
    {
        $default = trim($default);
        
        if ($default === '') {
            return $tokenWithoutDefault;
        }
        
        $trimmed = preg_replace('/\}$/', '', $tokenWithoutDefault);

        return $trimmed . '|' . $default . '}';
    }

    /**
     * Build a variable-picker-compatible reference token.
     *
     * Use this from Twig via `craft.formie.ref()` when overriding settings such as `submitActionMessage`.
     * Submit action messages do not evaluate Twig at submit time — they store reference tokens that
     * Formie resolves when the submission completes.
     *
     * Examples:
     * - token('submission', 'uid') => `{submission:uid}`
     * - token('allFields') => `{allFields}`
     * - token('field', 'a1b2c3', 'email') => `{field:a1b2c3:email}`
     */
    public static function token(
        string $target,
        string $identifier = '',
        ?string $selector = null,
        array $metadata = [],
        string $default = '',
    ): string {
        $target = trim($target);
        $identifier = trim($identifier);
        $selector = trim((string)$selector);
        $default = trim($default);

        if ($target === '') {
            throw new \InvalidArgumentException('Reference target cannot be empty.');
        }

        if (in_array($target, ['timestamp', 'allFields', 'allContentFields', 'allVisibleFields'], true) && $identifier === '') {
            $body = $target;

            if (isset($metadata['transform'])) {
                $body .= ';transform=' . rawurlencode((string)$metadata['transform']);
                unset($metadata['transform']);
            }

            foreach ($metadata as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $body .= ';' . $key . '=' . rawurlencode((string)$value);
            }

            $token = '{' . $body . '}';

            return $default !== '' ? self::withDefault($token, $default) : $token;
        }

        if ($target === 'field') {
            if ($identifier === '') {
                throw new \InvalidArgumentException('Field reference tokens require a field reference identifier.');
            }

            $token = self::field($identifier, $selector !== '' ? $selector : null, $metadata);

            return $default !== '' ? self::withDefault($token, $default) : $token;
        }

        if ($identifier === '') {
            throw new \InvalidArgumentException(sprintf('Reference token "%s" requires an identifier.', $target));
        }

        $body = $identifier;

        if ($selector !== '') {
            $body .= ':' . $selector;
        }

        if (isset($metadata['transform'])) {
            $body .= ';transform=' . rawurlencode((string)$metadata['transform']);
            unset($metadata['transform']);
        }

        foreach ($metadata as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $body .= ';' . $key . '=' . rawurlencode((string)$value);
        }

        $token = '{' . $target . ':' . $body . '}';

        return $default !== '' ? self::withDefault($token, $default) : $token;
    }

    public static function field(string $reference, ?string $selector = null, array $metadata = []): string
    {
        $reference = trim($reference);
        $selector = trim((string)$selector);
        $body = $reference;

        if ($selector !== '') {
            $body .= ':' . $selector;
        }

        if (isset($metadata['transform'])) {
            $body .= ';transform=' . rawurlencode((string)$metadata['transform']);
            unset($metadata['transform']);
        }

        foreach ($metadata as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $body .= ';' . $key . '=' . rawurlencode((string)$value);
        }

        return '{field:' . $body . '}';
    }

    /**
     * Parse content and interpolate references. Array values become comma-separated strings.
     */
    public static function parseListContent(string $content, Submission $submission, array $options = []): string
    {
        if (!str_contains($content, '{')) {
            return $content;
        }

        $variables = $options['variables'] ?? null;

        if (!is_array($variables)) {
            $notification = $options['notification'] ?? null;
            $includeSummary = (bool)($options['includeSummary'] ?? false);
            $variables = Variables::getVariablesForSubmission(
                $submission,
                $notification instanceof Notification ? $notification : null,
                $includeSummary,
                (bool)($options['parseEnvValues'] ?? true)
            );
        }

        return (string)preg_replace_callback('/\{[^{}]+\}/', function (array $matches) use ($submission, $variables) {
            $raw = (string)($matches[0] ?? '');
            $expr = self::parseReferenceExpression($raw);

            if (!$expr->isValid && $expr->target === '' && $expr->identifier === '') {
                return $raw;
            }

            $resolved = self::parseValue($raw, $submission, ['variables' => $variables]);

            return self::_stringifyListValue($resolved);
        }, $content);
    }

    public static function hasRepeaterScope(ReferenceExpression $expression): bool
    {
        $scope = $expression->transformerParams['scope'] ?? null;

        return is_string($scope) && trim($scope) !== '';
    }

    public static function extractFieldReferenceHandles(string $content): array
    {
        if (!str_contains($content, '{field:')) {
            return [];
        }

        $handles = [];

        preg_replace_callback('/\{[^{}]+\}/', function (array $matches) use (&$handles): string {
            $expression = self::parseReferenceExpression($matches[0]);

            if ($expression->isValid && $expression->target === 'field' && $expression->identifier !== '') {
                $handles[] = $expression->identifier;
            }

            return $matches[0];
        }, $content);

        return array_values(array_unique($handles));
    }

    public static function remapFieldReferenceToken(string $rawToken, array $referenceMap): string
    {
        $expression = self::parseReferenceExpression($rawToken);

        if (!$expression->isValid || $expression->target !== 'field' || !isset($referenceMap[$expression->identifier])) {
            return $rawToken;
        }

        $body = $referenceMap[$expression->identifier];

        if ($expression->selector !== '') {
            $body .= ':' . $expression->selector;
        }

        if ($expression->transformerId !== '') {
            $body .= ';transform=' . $expression->transformerId;

            foreach ($expression->transformerParams as $paramKey => $paramValue) {
                $body .= ';' . $paramKey . '=' . $paramValue;
            }
        }

        return self::withDefault('{field:' . $body . '}', $expression->default);
    }

    public static function submission(string $attribute): string
    {
        return '{submission:' . trim($attribute) . '}';
    }

    /**
     * Removes transform metadata from token body and returns [cleanBody, transformerId, transformerParams].
     */
    private static function _extractTransformMetadata(string $body): array
    {
        $transformerId = '';
        $transformerParams = [];

        $parts = array_values(array_filter(array_map('trim', explode(';', $body)), fn($part) => $part !== ''));
        if ($parts === []) {
            return ['', $transformerId, $transformerParams];
        }

        $cleanParts = [];

        $base = array_shift($parts);
        if (is_string($base) && trim($base) !== '') {
            $cleanParts[] = trim($base);
        }

        foreach ($parts as $part) {
            if (str_starts_with($part, 'transform=')) {
                $encoded = substr($part, strlen('transform='));
                $decoded = urldecode($encoded);
                $transformerId = trim($decoded);
                continue;
            }

            if (str_contains($part, '=')) {
                [$rawKey, $rawValue] = array_pad(explode('=', $part, 2), 2, '');
                $key = trim($rawKey);

                if ($key !== '' && $key !== 'transform') {
                    $transformerParams[$key] = urldecode(trim($rawValue));
                }

                continue;
            }

            $cleanParts[] = $part;
        }

        return [implode(';', $cleanParts), $transformerId, $transformerParams];
    }

    private static function _looksLikeReferenceToken(string $value): bool
    {
        $trimmed = trim($value);

        return str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}') && substr_count($trimmed, '{') === 1 && substr_count($trimmed, '}') === 1;
    }

    private static function _stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if ($value instanceof \Stringable) {
            return (string)$value;
        }

        return '';
    }

    private static function _stringifyListValue(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];

            foreach ($value as $item) {
                $string = self::_stringifyValue($item);

                if ($string !== '') {
                    $parts[] = $string;
                }
            }

            return implode(', ', $parts);
        }

        return self::_stringifyValue($value);
    }
}
