<?php
namespace verbb\formie\helpers;

use verbb\formie\elements\Submission;
use verbb\formie\models\ReferenceExpression;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDiagnostic;
use verbb\formie\references\ReferenceException;
use verbb\formie\references\ReferenceOutputContext;
use verbb\formie\references\ReferenceParser;
use verbb\formie\references\ReferenceResolver;
use verbb\formie\references\ReferenceSlot;
use verbb\formie\references\ReferenceSlotKind;
use verbb\formie\references\ResolvedReference;

/** Public bridge; all parsing and evaluation live in the shared reference runtime. */
class References
{
    // Static Methods
    // =========================================================================

    public static function resolveValue(string|ReferenceExpression $expression, ReferenceContext $context): ResolvedReference
    {
        return (new ReferenceResolver())->resolveValue($expression, $context);
    }

    public static function interpolateText(string $template, ReferenceContext $context, ReferenceOutputContext $outputContext = ReferenceOutputContext::PlainText): string
    {
        return (new ReferenceResolver())->interpolateText($template, $context, $outputContext);
    }

    public static function parseValue(mixed $value, Submission $submission, array $options = []): mixed
    {
        if (!is_string($value) || !preg_match('/^\s*\{[^{}]+\}\s*$/D', $value)) {
            return $value;
        }
        return self::resolveValue($value, ReferenceContext::forSubmission($submission, $options['notification'] ?? null))->requireValue();
    }

    public static function parseContent(string $content, Submission $submission, array $options = []): string
    {
        $output = $options['outputContext'] ?? ReferenceOutputContext::PlainText;
        return self::interpolateText($content, ReferenceContext::forSubmission($submission, $options['notification'] ?? null), $output);
    }

    public static function parseUrl(string $template, Submission $submission): string
    {
        return StringHelper::sanitizeRedirectUrl(self::resolveUrl($template, $submission));
    }

    public static function resolveUrl(string $template, Submission $submission): string
    {
        $context = ReferenceContext::forSubmission($submission);
        // Stable URL settings allowed a whole exact reference. This bounded legacy
        // slot adapter preserves that meaning; embedded values are URL components.
        $slot = ReferenceSlot::fromStored($template);
        if ($slot->kind === ReferenceSlotKind::Exact) {
            $value = $slot->resolve($context);
            if (!is_string($value)) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidType);
            }
        } else {
            $value = self::interpolateText($template, $context, ReferenceOutputContext::UrlComponent);
        }
        return $value;
    }

    public static function parseListContent(string $content, Submission $submission, array $options = []): string
    {
        return self::parseContent($content, $submission, $options);
    }

    public static function parseReferenceExpression(string $raw): ReferenceExpression
    {
        return ReferenceParser::parse($raw);
    }

    public static function token(string $target, string $identifier = '', ?string $selector = null, array $metadata = [], string $default = ''): string
    {
        $transform = $metadata['transform'] ?? '';
        unset($metadata['transform']);
        $expression = new ReferenceExpression(target: $target, identifier: $identifier, selector: $selector ?? '', default: $default, transformerId: $transform, transformerParams: $metadata, isValid: true);
        $token = ReferenceParser::serialize($expression);
        if (!ReferenceParser::parse($token)->isValid) {
            throw new \InvalidArgumentException('Invalid reference token components.');
        }
        return $token;
    }

    public static function field(string $reference, ?string $selector = null, array $metadata = []): string
    {
        return self::token('field', $reference, $selector, $metadata);
    }

    public static function submission(string $attribute): string
    {
        return self::token('submission', $attribute);
    }

    public static function withDefault(string $tokenWithoutDefault, string $default): string
    {
        $expression = ReferenceParser::parse($tokenWithoutDefault);
        return self::token($expression->target, $expression->identifier, $expression->selector, ['transform' => $expression->transformerId, ...$expression->transformerParams], $default);
    }

    public static function hasRepeaterScope(ReferenceExpression $expression): bool
    {
        return isset($expression->transformerParams['scope']);
    }

    public static function extractFieldReferenceHandles(string $content): array
    {
        preg_match_all('/\{[^{}]+\}/', $content, $matches);
        $handles = [];
        foreach ($matches[0] as $token) {
            $expression = ReferenceParser::parse($token);
            if ($expression->isValid && $expression->target === 'field') {
                $handles[] = $expression->identifier;
            }
        }
        return array_values(array_unique($handles));
    }

    public static function remapFieldReferenceToken(string $rawToken, array $referenceMap): string
    {
        $expression = ReferenceParser::parse($rawToken);
        if (!$expression->isValid || $expression->target !== 'field' || !isset($referenceMap[$expression->identifier])) {
            return $rawToken;
        }
        return self::token('field', $referenceMap[$expression->identifier], $expression->selector, ['transform' => $expression->transformerId, ...$expression->transformerParams], $expression->default);
    }
}
