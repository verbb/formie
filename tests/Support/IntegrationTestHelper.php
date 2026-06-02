<?php

declare(strict_types=1);

namespace Tests\Support;

use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Variables;

/**
 * Helpers for integration field mapping and opt-in tests.
 * Populates the RenderCache with field values from a submission so that
 * Variables::getFieldAndValueForReference() resolves correctly in tests.
 */
final class IntegrationTestHelper
{
    /**
     * Build variable-style field values from the submission (field.reference => value)
     * and merge them into the cache so getVariablesForSubmission / getFieldAndValueForReference
     * return the submission's actual field values.
     *
     * @param array<string, mixed>|null $valueOverrides Optional handle => value to use instead of getFieldValue (e.g. truthy test values).
     */
    public static function primeVariableCacheForSubmission(Submission $submission, ?array $valueOverrides = null): void
    {
        $cacheKey = $submission->id ? 'submission' . $submission->id : 'form' . ($submission->form?->id ?? 'new');
        Variables::getVariablesForSubmission($submission);
        $fieldVars = self::buildFieldVariablesFromSubmission($submission, $valueOverrides);
        Formie::$plugin->getRenderCache()->setFieldVariables($cacheKey, $fieldVars);
    }

    /**
     * Build the "field.reference" => value map from the submission's fields and values.
     * Uses nested keys (e.g. ['field' => ['text' => value]]) so Variables::getFieldAndValueForReference
     * and ArrayHelper::getValue($variables, 'field.text') resolve correctly.
     *
     * @param array<string, mixed>|null $valueOverrides Optional handle => value map to use instead of getFieldValue (e.g. in tests when content may not be loaded).
     */
    public static function buildFieldVariablesFromSubmission(Submission $submission, ?array $valueOverrides = null): array
    {
        $vars = [];
        foreach ($submission->getFields() as $field) {
            $ref = $field->reference ?? $field->handle;
            if ($ref === '' || $ref === null) {
                continue;
            }
            $value = $valueOverrides !== null && array_key_exists($field->handle, $valueOverrides)
                ? $valueOverrides[$field->handle]
                : $submission->getFieldValue($field->handle);
            $key = 'field.' . str_replace(':', '.', $ref);
            self::setValueByPath($vars, $key, $value);
        }
        return $vars;
    }

    /**
     * Set a value in an array by dot path (e.g. 'field.handle' or 'field.formId.handle').
     * Modifies $array in place.
     */
    public static function setValueByPath(array &$array, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $current = &$array;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current[array_shift($keys)] = $value;
    }
}
