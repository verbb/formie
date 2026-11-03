<?php
namespace verbb\formie\references;

use verbb\formie\base\ContainerParentFieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\ReferenceExpression;

/** Lossless read/save migration restricted to known reference-bearing settings. */
final class ReferenceMigration
{
    // Static Methods
    // =========================================================================

    public static function integrationSlots(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (str_ends_with(strtolower((string)$key), 'fieldmapping') || in_array($key, ['attributeMapping', 'emailSendMapping'], true)) {
                foreach ($value as $destination => $slot) {
                    $settings[$key][$destination] = ReferenceSlot::fromStored($slot)->toArray();
                }
            } else {
                $settings[$key] = self::integrationSlots($value);
            }
        }

        return $settings;
    }

    public static function canonicalFieldTokens(Form $form, string $value): string
    {
        return self::canonicalFieldTokensForFields($form->getFields(), $value);
    }

    public static function canonicalFieldTokensForFields(array $fields, string $value): string
    {
        if (!str_contains($value, '{field:')) {
            return $value;
        }

        return preg_replace_callback('/\{field:[^{}]+\}/', static function(array $match) use ($fields): string {
            $expression = ReferenceParser::parse($match[0]);

            // Formie 3 stored field selectors as handle.selector, including Phone, Name and Address parts,
            // and Group children as group.child. Unknown paths must remain available for diagnosis.
            if ($expression->isValid && $expression->target === 'field' && $expression->selector === '' && str_contains($expression->identifier, '.')) {
                $path = self::_legacyFieldPath($fields, $expression->identifier);

                if ($path) {
                    return ReferenceParser::serialize(new ReferenceExpression(
                        raw: $expression->raw,
                        target: 'field',
                        identifier: (string)$path[0]->reference,
                        selector: $path[1],
                        default: $expression->default,
                        transformerId: $expression->transformerId,
                        transformerParams: $expression->transformerParams,
                        isValid: true,
                    ));
                }
            }

            if (!$expression->isValid || $expression->target !== 'field' || $expression->selector === '') {
                return $match[0];
            }

            $field = null;

            foreach ($fields as $candidate) {
                if (in_array($expression->identifier, [(string)$candidate->reference, (string)$candidate->uid, (string)$candidate->handle], true)) {
                    $field = $candidate;

                    break;
                }
            }

            if (!$field instanceof ParentFieldInterface) {
                return $match[0];
            }

            foreach ($field->referenceValues() as $referenceValue) {
                if ($referenceValue->selector === $expression->selector && $referenceValue->appliesTo($field)) {
                    return $match[0];
                }
            }

            $parts = explode(':', $expression->selector);
            $migrated = false;

            while ($field instanceof ParentFieldInterface && $parts !== []) {
                $child = $field->getFieldByHandle($parts[0]);

                if (!$child || !$child->reference) {
                    break;
                }

                array_shift($parts);
                $field = $child;
                $migrated = true;
            }

            if (!$migrated) {
                return $match[0];
            }

            return ReferenceParser::serialize(new ReferenceExpression(
                raw: $expression->raw,
                target: 'field',
                identifier: (string)$field->reference,
                selector: implode(':', $parts),
                default: $expression->default,
                transformerId: $expression->transformerId,
                transformerParams: $expression->transformerParams,
                isValid: true,
            ));
        }, $value);
    }


    // Private Methods
    // =========================================================================

    private static function _legacyFieldPath(array $fields, string $path): ?array
    {
        [$identifier, $selector] = array_pad(explode('.', $path, 2), 2, '');

        foreach ($fields as $field) {
            if (!$field->reference || !in_array($identifier, [(string)$field->reference, (string)$field->uid, (string)$field->handle], true)) {
                continue;
            }

            if ($selector === '') {
                return [$field, ''];
            }

            // Row values need a scope that a plain path can't express.
            if ($field instanceof RepeatableParentFieldInterface) {
                return null;
            }

            // A path into a Group always meant a child. Its own selectors didn't exist in Formie 3.
            if (!$field instanceof ContainerParentFieldInterface) {
                foreach ($field->referenceValues() as $referenceValue) {
                    if ($referenceValue->selector === $selector && $referenceValue->appliesTo($field)) {
                        return [$field, $selector];
                    }
                }
            }

            return $field instanceof ParentFieldInterface ? self::_legacyFieldPath($field->getFields(), $selector) : null;
        }

        return null;
    }
}
