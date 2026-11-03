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
}
