<?php
namespace verbb\formie\references;

use verbb\formie\base\ParentFieldInterface;
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
}
