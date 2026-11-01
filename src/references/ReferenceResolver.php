<?php
namespace verbb\formie\references;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\HtmlReferenceFieldInterface;
use verbb\formie\compatibility\fields\FieldCompatibility;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\models\ReferenceExpression;

use Html2Text\Html2Text;

/** Coordinates grammar, explicit sources and field-owned projections. */
final class ReferenceResolver
{
    // Public Methods
    // =========================================================================

    public function resolveValue(string|ReferenceExpression $expression, ReferenceContext $context): ResolvedReference
    {
        $expression = is_string($expression) ? ReferenceParser::parse($expression) : $expression;

        if (!$expression->isValid || $expression->version !== 1) {
            return new ResolvedReference($expression, diagnostic: ReferenceDiagnostic::InvalidExpression);
        }

        try {
            if ($expression->transformerId === '' && array_diff(array_keys($expression->transformerParams), ['scope', 'index', 'rows'])) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidExpression);
            }

            if ($expression->target !== 'field' && array_intersect(array_keys($expression->transformerParams), ['scope', 'index', 'rows'])) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidExpression);
            }

            $result = $expression->target === 'field' ? (new FieldReferenceResolver())->resolve($expression, $context) : (new ContextReferenceSource())->resolve($expression, $context);
            $value = $result->requireValue();

            if ($expression->transformerId !== '') {
                if (!$result->definition->allowTransforms) {
                    throw new ReferenceException(ReferenceDiagnostic::UnknownTransform);
                }

                if ($expression->target === 'custom' && !in_array($expression->transformerId, $result->definition->transforms, true)) {
                    throw new ReferenceException(ReferenceDiagnostic::UnknownTransform);
                }

                $value = $this->_transform($value, $expression, $context, $result->field);
            }

            // A default replaces a resolved empty value; it never masks a deleted/forbidden source.
            if (($value === null || $value === '' || $value === [] || ($value instanceof FieldValueInterface && $value->isEmpty())) && $expression->default !== '') {
                $value = $expression->default;
            }

            $projection = $result->fieldProjection;

            if (in_array($expression->transformerId, ['lower', 'upper', 'title', 'capitalize', 'kebab', 'replace', 'truncate'], true)) {
                // Text transforms have already applied the specialist projection.
                $projection = 'none';
            } elseif ($projection === 'collection' && in_array($expression->transformerId, ['first', 'last'], true)) {
                $projection = 'value';
            }

            // Only summaries adopt the destination's format; existing scalar mappings keep their projection.
            if ($result->definition->shape === ReferenceShape::Block && $context->usage === ReferenceUsage::Integration && $context->summaryOutputContext === ReferenceOutputContext::PlainText) {
                $value = (new Html2Text((string)$value, ['width' => 0]))->getText();
            }

            return new ResolvedReference($expression, $value, $result->definition, field: $result->field, fieldProjection: $projection);
        } catch (ReferenceException $e) {
            return new ResolvedReference($expression, diagnostic: $e->diagnostic);
        }
    }

    public function interpolateText(string $template, ReferenceContext $context, ReferenceOutputContext $outputContext = ReferenceOutputContext::PlainText, bool $allowFieldHtml = false): string
    {
        $context = $context->withOutputContext($outputContext);

        if ($outputContext === ReferenceOutputContext::EmailHeader && preg_match('/[\r\n\x00]/', $template)) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidOutput);
        }

        return preg_replace_callback('/(?<!\{)\{[^{}]+\}(?!\})/', function(array $match) use ($context, $outputContext, $template, $allowFieldHtml): string {
            [$token, $offset] = $match[0];

            if (preg_match('/^\{\s/', $token)) {
                return $token;
            }

            $result = $this->resolveValue($token, $context);
            $expression = $result->expression;

            if ($result->diagnostic !== null) {
                $strictOutput = in_array($context->usage, [ReferenceUsage::Integration, ReferenceUsage::Url], true) || in_array($outputContext, [
                    ReferenceOutputContext::EmailHeader,
                    ReferenceOutputContext::UrlComponent,
                    ReferenceOutputContext::StructuredData,
                ], true);

                if ($result->diagnostic === ReferenceDiagnostic::UnknownSource && !$this->_knownTarget($expression->target) && !$strictOutput) {
                    return $token;
                }

                if ($strictOutput) {
                    $result->requireValue();
                }

                $context->diagnostics->add($token, $result->diagnostic);

                return $expression->default;
            }

            $value = $result->requireValue();
            $summary = in_array($expression->target, ['allFields', 'allContentFields', 'allVisibleFields'], true) || $result->fieldProjection === 'summary';

            if ($result->field && $result->fieldProjection !== 'none') {
                $field = $result->field;
                $project = static fn(mixed $item): mixed => $outputContext === ReferenceOutputContext::StructuredData
                    ? $field->getValueAsData($item, $context->submission)
                    : FieldCompatibility::withReferenceNotification($field, $context->notification, fn() => $field->getValueForReference($item, $context->submission));

                if ($result->fieldProjection === 'value' && $field->valueType()->accepts($value)) {
                    $value = $project($value);
                } elseif ($result->fieldProjection === 'collection' && is_array($value) && count(array_filter($value, static fn(mixed $item): bool => $field->valueType()->accepts($item))) === count($value)) {
                    $value = array_map($project, $value);
                }
            }

            // Field-owned HTML is trusted only as element content, never inside a tag, attribute, comment or raw-text element.
            if ($allowFieldHtml && $outputContext === ReferenceOutputContext::Html && $result->field instanceof HtmlReferenceFieldInterface && $result->fieldProjection === 'value' && $expression->transformerId === '' && $this->_isTextPosition($template, $offset)) {
                $html = $result->field->getValueForHtmlReference($value, $context->submission);

                if ($html !== null) {
                    return $html;
                }
            }

            if ($summary && $outputContext === ReferenceOutputContext::Html) {
                // Only registered, field-owned summary templates can produce trusted block HTML.
                return (string)$value;
            }

            return $this->_encode($value, $outputContext);
        }, $template, flags: PREG_OFFSET_CAPTURE);
    }

    public function fieldFor(string $identifier, ReferenceContext $context): ?FieldInterface
    {
        return (new FieldReferenceResolver())->findField($identifier, $context)['field'] ?? null;
    }


    // Private Methods
    // =========================================================================

    private function _transform(mixed $value, ReferenceExpression $expression, ReferenceContext $context, ?FieldInterface $field): mixed
    {
        $id = $expression->transformerId;

        if (in_array($expression->target, ['allFields', 'allContentFields', 'allVisibleFields'], true)) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidType);
        }

        $custom = Formie::$plugin->getReferenceCatalogue()->transform($id);

        if ($custom) {
            if (!$custom->server || !in_array('server', $context->permissions, true)) {
                throw new ReferenceException(ReferenceDiagnostic::ForbiddenSource);
            }

            if (!$custom->inputType->accepts($value) || array_diff(array_keys($expression->transformerParams), [...$custom->parameters, 'scope', 'index', 'rows'])) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidType);
            }

            $result = ($custom->transform)($value, $expression->transformerParams, $context);

            if (!$custom->outputType->accepts($result)) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidType);
            }

            return $result;
        }

        $parameters = BuiltinReferenceTransforms::parameters($id);

        if ($parameters === null) {
            throw new ReferenceException(ReferenceDiagnostic::UnknownTransform);
        }

        if (array_diff(array_keys($expression->transformerParams), [...$parameters, 'scope', 'index', 'rows'])) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidExpression);
        }

        if (in_array($id, ['round', 'floor', 'ceil'], true) && !is_numeric($value)) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidType);
        }

        if (in_array($id, ['join', 'first', 'last', 'count'], true) && !is_array($value)) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidType);
        }

        if (in_array($id, ['lower', 'upper', 'title', 'capitalize', 'kebab', 'replace', 'truncate'], true) && $field && $field->valueType()->accepts($value)) {
            $value = FieldCompatibility::withReferenceNotification($field, $context->notification, fn() => $field->getValueForReference($value, $context->submission));
        }

        if (in_array($id, ['lower', 'upper', 'title', 'capitalize', 'kebab', 'replace', 'truncate'], true) && !is_scalar($value) && $value !== null) {
            throw new ReferenceException(ReferenceDiagnostic::InvalidType);
        }

        return BuiltinReferenceTransforms::apply($value, $id, $expression->transformerParams);
    }

    private function _knownTarget(string $target): bool
    {
        return in_array($target, [
            'field', 'form', 'submission', 'site', 'user', 'system', 'timestamp',
            'allFields', 'allContentFields', 'allVisibleFields', 'env', 'metadata',
            'report', 'dispatch', 'custom',
        ], true);
    }

    private function _isTextPosition(string $template, int $offset): bool
    {
        $before = substr($template, 0, $offset);

        // Comments and raw-text elements change how the markup after them is parsed.
        if (preg_match('/<!--|<(?:script|style|textarea|title|xmp|iframe|noembed|noframes|noscript|plaintext)\b/i', $before)) {
            return false;
        }

        // Follow the HTML tokenizer's quoting, so a `>` inside an attribute value does not end the tag.
        $inTag = false;
        $quote = null;
        $afterEquals = false;

        for ($i = 0, $length = strlen($before); $i < $length; $i++) {
            $char = $before[$i];

            if ($quote !== null) {
                $quote = $char === $quote ? null : $quote;
            } elseif (!$inTag) {
                $inTag = $char === '<' && preg_match('/[a-z\/!?]/i', $before[$i + 1] ?? '') === 1;
                $afterEquals = false;
            } elseif ($char === '>') {
                $inTag = false;
            } elseif ($afterEquals && ($char === '"' || $char === "'")) {
                $quote = $char;
                $afterEquals = false;
            } elseif (!ctype_space($char)) {
                $afterEquals = $char === '=';
            }
        }

        return !$inTag;
    }

    private function _encode(mixed $value, ReferenceOutputContext $outputContext): string
    {
        if ($outputContext === ReferenceOutputContext::StructuredData) {
            if (!FieldValueType::storageSafe()->accepts($value)) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidType);
            }

            return json_encode($value, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        }

        $text = $this->_text($value);

        return match ($outputContext) {
            ReferenceOutputContext::Html => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ReferenceOutputContext::UrlComponent => rawurlencode($text),
            ReferenceOutputContext::EmailHeader => preg_match('/[\r\n\x00]/', $text) ? throw new ReferenceException(ReferenceDiagnostic::InvalidOutput) : $text,
            default => $text,
        };
    }

    private function _text(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return is_bool($value) ? ($value ? '1' : '0') : (string)$value;
        }

        if (is_array($value)) {
            return implode(', ', array_map($this->_text(...), $value));
        }

        throw new ReferenceException(ReferenceDiagnostic::InvalidType);
    }
}
