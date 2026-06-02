<?php
namespace verbb\formie\deprecations;

use Craft;

trait SubmissionValueDeprecations
{
    // Public Methods
    // =========================================================================

    public function getValueAsString(string $fieldHandle): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getValueAsString()` has been deprecated. Use `getFieldValueAsString()` instead.');

        return $this->getFieldValueAsString($fieldHandle);
    }

    public function getValueAsJson(string $fieldHandle): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getValueAsJson()` has been deprecated. Use `getFieldValueAsArray()` instead.');

        return $this->getFieldValueAsArray($fieldHandle);
    }

    public function getValueForExport(string $fieldHandle): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getValueForExport()` has been deprecated. Use `getFieldValueForExport()` instead.');

        return $this->getFieldValueForExport($fieldHandle);
    }

    public function getValueForSummary(string $fieldHandle): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getValueForSummary()` has been deprecated. Use `getFieldValueForSummary()` instead.');

        return $this->getFieldValueForSummary($fieldHandle);
    }

    public function getFieldValueAsJson(string $fieldHandle): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getFieldValueAsJson()` has been deprecated. Use `getFieldValueAsArray()` instead.');

        return $this->getFieldValueAsArray($fieldHandle);
    }

    public function getValuesAsJson(): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getValuesAsJson()` has been deprecated. Use `getValuesAsArray()` instead.');

        return $this->getValuesAsArray();
    }

    public function getFieldValueForEmail(string $fieldHandle, mixed $notification): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getFieldValueForEmail()` has been deprecated. Use `getFieldValueForReferenceBlock()` instead.');

        return $this->getFieldValueForReferenceBlock($fieldHandle, $notification);
    }

    public function getFieldValueForVariable(string $fieldHandle, mixed $notification): mixed
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission `getFieldValueForVariable()` has been deprecated. Use `getFieldValueForReference()` instead.');

        return $this->getFieldValueForReference($fieldHandle, $notification);
    }
}
