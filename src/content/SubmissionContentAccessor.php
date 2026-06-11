<?php
namespace verbb\formie\content;

use verbb\formie\fields\values\FieldValueInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use craft\errors\InvalidFieldException;

class SubmissionContentAccessor
{
    // Public Methods
    // =========================================================================

    public function setRawValue(Submission $submission, string $fieldPath, mixed $value): void
    {
        $state = $submission->getContentState();
        [$rootHandle, $nestedPath] = $this->splitFieldPath($fieldPath);
        $field = $submission->getContentManager()->getFieldByHandle($submission, $rootHandle);

        if (!$field) {
            throw new InvalidFieldException($rootHandle);
        }

        $uid = $field->uid;

        if ($nestedPath === null) {
            $state->rawValuesByUid[$uid] = $value;
            unset($state->normalizedValuesByUid[$uid]);
            return;
        }

        if (array_key_exists($uid, $state->rawValuesByUid)) {
            $rootValue = $state->rawValuesByUid[$uid];
        } else if (array_key_exists($uid, $state->normalizedValuesByUid)) {
            // If callers mutate a nested path after normalization has already
            // happened, step back through the normalized root value instead of
            // discarding the currently hydrated object graph.
            $rootValue = $state->normalizedValuesByUid[$uid];
        } else {
            $rootValue = [];
        }

        if ($rootValue instanceof FieldValueInterface) {
            $rootValue = $rootValue->toValueArray();
        }

        if (!is_array($rootValue) && !$rootValue instanceof \ArrayAccess && !$rootValue instanceof \stdClass) {
            $rootValue = [];
        }

        ArrayHelper::setValue($rootValue, $nestedPath, $value);
        $state->rawValuesByUid[$uid] = $rootValue;
        unset($state->normalizedValuesByUid[$uid]);
    }

    public function setNormalizedValue(Submission $submission, string $fieldHandle, mixed $value): void
    {
        $state = $submission->getContentState();
        $field = $submission->getContentManager()->getFieldByHandle($submission, $fieldHandle);

        if (!$field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $state->rawValuesByUid[$field->uid] = $value;
        $state->normalizedValuesByUid[$field->uid] = $value;
    }

    public function getNormalizedValue(Submission $submission, string $fieldPath): mixed
    {
        [$rootHandle, $nestedPath] = $this->splitFieldPath($fieldPath);
        $rootValue = $this->_getRootNormalizedValue($submission, $rootHandle);

        if ($nestedPath === null) {
            return $rootValue;
        }

        $field = $submission->getContentManager()->getFieldByHandle($submission, $rootHandle);

        if ($field instanceof \verbb\formie\fields\Date) {
            return $field->resolveNormalizedValuePath($rootValue, $nestedPath);
        }

        return $this->resolvePathValue($rootValue, $nestedPath);
    }

    public function cloneNormalizedValue(Submission $submission, string $fieldPath): mixed
    {
        $value = $this->getNormalizedValue($submission, $fieldPath);

        if (is_object($value) && !$value instanceof \UnitEnum) {
            return clone $value;
        }

        return $value;
    }

    // Compatibility wrapper for existing callsites during migration.
    public function setValue(Submission $submission, string $fieldPath, mixed $value): void
    {
        $this->setRawValue($submission, $fieldPath, $value);
    }

    // Compatibility wrapper for existing callsites during migration.
    public function getValue(Submission $submission, string $fieldPath): mixed
    {
        return $this->getNormalizedValue($submission, $fieldPath);
    }

    // Compatibility wrapper for existing callsites during migration.
    public function cloneValue(Submission $submission, string $fieldPath): mixed
    {
        return $this->cloneNormalizedValue($submission, $fieldPath);
    }

    public function getPathValue(Submission $submission, string $fieldPath): mixed
    {
        return $this->getNormalizedValue($submission, $fieldPath);
    }

    public function resolvePathValue(mixed $fieldValue, ?string $path): mixed
    {
        if ($path === null) {
            return $fieldValue;
        }

        if ($fieldValue instanceof FieldValueInterface) {
            return $fieldValue->getPathValue($path);
        }

        if (is_array($fieldValue) || $fieldValue instanceof \ArrayAccess || $fieldValue instanceof \stdClass) {
            return ArrayHelper::getValue($fieldValue, $path);
        }

        return $fieldValue;
    }

    public function splitFieldPath(string $fieldPath): array
    {
        $segments = explode('.', $fieldPath);
        $rootHandle = array_shift($segments);
        $nestedPath = $segments ? implode('.', $segments) : null;

        return [$rootHandle, $nestedPath];
    }


    // Private Methods
    // =========================================================================

    private function _getRootNormalizedValue(Submission $submission, string $fieldHandle): mixed
    {
        $state = $submission->getContentState();
        $field = $submission->getContentManager()->getFieldByHandle($submission, $fieldHandle);

        if (!$field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $uid = $field->uid;

        if (array_key_exists($uid, $state->normalizedValuesByUid)) {
            return $state->normalizedValuesByUid[$uid];
        }

        // Cache normalization per field uid for the lifetime of the submission
        // state so repeated template, export, and workflow reads do not keep
        // re-running field-specific normalization logic.
        $rawValue = $state->rawValuesByUid[$uid] ?? null;
        $normalizedValue = $field->normalizeValue($rawValue, $submission);
        $state->normalizedValuesByUid[$uid] = $normalizedValue;
        $state->rawValuesByUid[$uid] = $rawValue;

        return $normalizedValue;
    }

}
