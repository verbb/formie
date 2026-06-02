<?php
namespace verbb\formie\compatibility\schema;

use verbb\formie\Formie;

use Craft;

class SchemaCompatibility
{
    private static array $_legacyWarnings = [];

    // Public Methods
    // =========================================================================

    public static function normalizeLegacyNode(array $node): array
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            return $node;
        }

        if (array_key_exists('$formkit', $node) && !array_key_exists('$field', $node)) {
            $node['$field'] = $node['$formkit'];
            unset($node['$formkit']);

            self::logLegacySchemaDeprecation(
                'formkit',
                'Schema `$formkit` nodes have been deprecated. Use `$field` instead.'
            );
        }

        if (array_key_exists('help', $node) && !array_key_exists('instructions', $node)) {
            $node['instructions'] = $node['help'];
            unset($node['help']);

            self::logLegacySchemaDeprecation(
                'help',
                'Schema `help` has been deprecated. Use `instructions` instead.'
            );
        }

        if (isset($node['if']) && is_string($node['if'])) {
            $normalizedCondition = self::normalizeLegacyCondition($node['if']);

            if ($normalizedCondition !== $node['if']) {
                $node['if'] = $normalizedCondition;

                self::logLegacySchemaDeprecation(
                    'if',
                    'Legacy schema `if` expressions using `$get(...).value` have been deprecated. Use direct field expressions instead.'
                );
            }
        }

        return $node;
    }

    // Private Methods
    // =========================================================================

    private static function normalizeLegacyCondition(string $condition): string
    {
        $normalized = preg_replace('/\$get\(([^)]+)\)\.value/', '$1', $condition) ?? $condition;

        $normalized = preg_replace_callback('/([=!]=|[=!]==)\s*([A-Za-z_][A-Za-z0-9_]*)/', function(array $matches) {
            $operator = $matches[1] ?? '';
            $value = $matches[2] ?? '';

            if (in_array(strtolower($value), ['true', 'false', 'null'], true)) {
                return "{$operator} {$value}";
            }

            return sprintf('%s "%s"', $operator, $value);
        }, $normalized) ?? $normalized;

        return $normalized;
    }

    private static function logLegacySchemaDeprecation(string $key, string $message): void
    {
        if (isset(self::$_legacyWarnings[$key])) {
            return;
        }

        self::$_legacyWarnings[$key] = true;

        Craft::$app->getDeprecator()->log(__METHOD__ . ':' . $key, $message);
    }
}
