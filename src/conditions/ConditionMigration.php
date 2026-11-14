<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Form;
use verbb\formie\helpers\References;
use verbb\formie\models\ReferenceExpression;
use verbb\formie\references\FieldReferenceResolver;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceException;
use verbb\formie\references\ReferenceParser;

final class ConditionMigration
{
    // Static Methods
    // =========================================================================

    public static function migrate(array $data): array
    {
        if (isset($data['conditions']) && is_array($data['conditions']) && isset($data['conditionRule'])) {
            $legacy = !isset($data['version']);
            $data['version'] ??= 1;

            foreach ($data['conditions'] as &$row) {
                if (is_array($row)) {
                    $operator = $row['condition'] ?? '';

                    if (is_string($operator)) {
                        $row['condition'] = ['==' => '=', 'equals' => '=', 'notEquals' => '!='][$operator] ?? $operator;
                    }

                    $row['legacyForward'] ??= $legacy;
                }
            }

            unset($row);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::migrate($value);
            }
        }

        return $data;
    }

    /** The builder only offers reference tokens, so replace the identities Formie 3 stored in condition rows. */
    public static function fieldReferences(array $data, Form $form): array
    {
        if (is_string($data['field'] ?? null) && array_key_exists('condition', $data)) {
            $data['field'] = self::_fieldReference($data['field'], $form);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::fieldReferences($value, $form);
            }
        }

        return $data;
    }

    /** Older versions stored shapes that name a real field but that nothing can read, so rewrite those condition rows. */
    public static function legacyFieldTokens(array $data, Form $form, bool $useReferences = true): array
    {
        if (is_string($data['field'] ?? null) && array_key_exists('condition', $data)) {
            $data['field'] = self::_legacyFieldToken($data['field'], $form, $useReferences);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::legacyFieldTokens($value, $form, $useReferences);
            }
        }

        return $data;
    }


    // Private Methods
    // =========================================================================

    private static function _fieldReference(string $source, Form $form): string
    {
        $expression = ReferenceParser::parse(str_starts_with($source, '{') || $source === '' ? $source : References::field($source));

        if (!$expression->isValid || $expression->target !== 'field' || $expression->selector !== '') {
            return $source;
        }

        // Formie 3's string form of a field with sub-fields is the whole field's value.
        $identifier = preg_replace('/\.__toString$/D', '', $expression->identifier);

        // Other paths can name a selector rather than a child, which ReferenceMigration decides.
        if ($identifier === $expression->identifier && str_contains($identifier, '.')) {
            return $source;
        }

        try {
            $entry = (new FieldReferenceResolver())->findField($identifier, new ReferenceContext(form: $form));
        } catch (ReferenceException) {
            return $source;
        }

        // Row values need a scope that a plain handle can't express.
        if (!$entry || !$entry['field']->reference || array_filter($entry['parts'], 'is_array')) {
            return $source;
        }

        return ReferenceParser::serialize(new ReferenceExpression(
            raw: $expression->raw,
            target: 'field',
            identifier: (string)$entry['field']->reference,
            selector: '',
            default: $expression->default,
            transformerId: $expression->transformerId,
            transformerParams: $expression->transformerParams,
            isValid: true,
        ));
    }

    private static function _legacyFieldToken(string $source, Form $form, bool $useReferences): string
    {
        // Only a bare path or a field path can be legacy. Anything with a target or parameters is already current.
        if (!preg_match('/^\{(field:)?([^{}:;]+)\}$/D', $source, $match)) {
            return $source;
        }

        $path = preg_replace('/\.rows\.[^.]+\.fields\./', '.', $match[2]);
        $path = str_replace('.__ROW__.', '.', $path);

        if ($match[1] !== '' && $path === $match[2]) {
            return $source;
        }

        $target = self::_legacyFieldTarget($path, $form);

        // Nothing by that name, so it stays visible as the stored token rather than becoming a different field.
        if (!$target) {
            return $source;
        }

        $identifier = $useReferences && $target['field']->reference ? (string)$target['field']->reference : $target['path'];

        return ReferenceParser::serialize(new ReferenceExpression(
            raw: $source,
            target: 'field',
            identifier: $identifier,
            selector: $target['selector'],
            default: '',
            transformerId: '',
            transformerParams: [],
            isValid: true,
        ));
    }

    private static function _legacyFieldTarget(string $path, Form $form): ?array
    {
        $resolver = new FieldReferenceResolver();
        $selector = [];
        $parts = explode('.', $path);

        // The longest path that names a field wins, and whatever's left has to be one of its own selectors.
        while ($parts) {
            try {
                $entry = $resolver->findField(implode('.', $parts), new ReferenceContext(form: $form));
            } catch (ReferenceException) {
                return null;
            }

            if ($entry) {
                $selector = implode(':', $selector);

                if ($selector === '') {
                    return ['field' => $entry['field'], 'path' => $entry['path'], 'selector' => ''];
                }

                foreach ($entry['field']->referenceValues() as $referenceValue) {
                    if ($referenceValue->matchesSelector($selector) && $referenceValue->appliesTo($entry['field'])) {
                        return ['field' => $entry['field'], 'path' => $entry['path'], 'selector' => $selector];
                    }
                }

                return null;
            }

            array_unshift($selector, array_pop($parts));
        }

        return null;
    }
}
