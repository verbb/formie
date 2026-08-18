<?php
namespace verbb\formie\helpers;

use verbb\formie\base\ElementField;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;

/**
 * Resolves `{field:<ref>:property}` tokens for element relation fields.
 *
 * Unlike Group/Name dotted paths, selectors here address related-element
 * properties (title, url, …). Unindexed selectors map over all related
 * elements; numeric prefixes keep single-element addressing (`0:title`).
 */
class ElementReferenceHelper
{
    // Static Methods
    // =========================================================================

    public static function resolve(
        Submission $submission,
        FieldInterface $field,
        string $selector,
        array $params = [],
    ): mixed {
        if (!$field instanceof ElementField) {
            return null;
        }

        $fieldKey = $field->valueKey();
        $value = $submission->getFieldValue($fieldKey);

        if ($selector === '') {
            return $value;
        }

        $elements = self::_elementsFromValue($value);

        return self::resolveFromElements($field, $elements, $selector, $params);
    }

    /**
     * @param ElementInterface[] $elements
     */
    public static function resolveFromElements(
        ElementField $field,
        array $elements,
        string $selector,
        array $params = [],
    ): mixed {
        [$property, $index] = self::parseSelector($selector, $params);

        if ($index !== null) {
            $element = $elements[$index] ?? null;

            return $element instanceof ElementInterface
                ? $field->resolveElementProperty($element, $property)
                : null;
        }

        $values = [];

        foreach ($elements as $element) {
            if (!$element instanceof ElementInterface) {
                continue;
            }

            $resolved = $field->resolveElementProperty($element, $property);

            if ($resolved === null || $resolved === '') {
                continue;
            }

            $values[] = $resolved;
        }

        if ($values === []) {
            return null;
        }

        if (count($values) === 1) {
            return $values[0];
        }

        // Notification bodies expect a single string; match defineValueAsString().
        return implode(', ', array_map(static function(mixed $value): string {
            if (is_scalar($value) || $value instanceof \Stringable) {
                return (string)$value;
            }

            return Json::encode($value) ?: '';
        }, $values));
    }

    /**
     * @return array{0: string, 1: int|null} [property path, optional 0-based index]
     */
    public static function parseSelector(string $selector, array $params = []): array
    {
        $index = isset($params['index']) && is_numeric($params['index']) ? (int)$params['index'] : null;
        $parts = array_values(array_filter(explode(':', $selector), static fn(string $part): bool => $part !== ''));

        if ($parts !== [] && is_numeric($parts[0]) && $index === null) {
            $index = (int)$parts[0];
            array_shift($parts);
        }

        return [implode('.', $parts), $index];
    }


    // Private Methods
    // =========================================================================

    /**
     * @return ElementInterface[]
     */
    private static function _elementsFromValue(mixed $value): array
    {
        if ($value instanceof ElementQueryInterface) {
            return $value->all();
        }

        if ($value instanceof ElementInterface) {
            return [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn(mixed $item): bool => $item instanceof ElementInterface,
        ));
    }
}
