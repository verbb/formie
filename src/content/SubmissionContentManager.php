<?php
namespace verbb\formie\content;

use verbb\formie\Formie;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\deprecations\SubmissionContentManagerDeprecations;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Variables;

use Throwable;

class SubmissionContentManager
{
    // Properties
    // =========================================================================

    private ?SubmissionContentAccessor $_accessor = null;
    private ?SubmissionContentNormalizer $_normalizer = null;
    private ?SubmissionContentSerializer $_serializer = null;
    private ?SubmissionContentProjector $_projector = null;


    // Traits
    // =========================================================================

    use SubmissionContentManagerDeprecations;


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        $this->_accessor = new SubmissionContentAccessor();
        $this->_normalizer = new SubmissionContentNormalizer();
        $this->_serializer = new SubmissionContentSerializer();
        $this->_projector = new SubmissionContentProjector();
    }

    public function getFieldCollection(Submission $submission): SubmissionFieldCollection
    {
        $state = $submission->getContentState();

        // Cache the field lookup indexes with the submission content state so
        // handle/id/uid resolution and persisted-field filtering stay cheap once
        // a submission's field layout has been touched.
        $state->fieldCollection ??= SubmissionFieldCollection::fromSubmission($submission);

        return $state->fieldCollection;
    }

    public function resetFieldCollection(Submission $submission): void
    {
        $submission->getContentState()->resetFieldCollection();
    }

    public function getFieldByHandle(Submission $submission, string $handle): ?FieldInterface
    {
        return $this->getFieldCollection($submission)->getByHandle($handle);
    }

    public function getFieldById(Submission $submission, int $id): ?FieldInterface
    {
        return $this->getFieldCollection($submission)->getById($id);
    }

    public function getFieldByUid(Submission $submission, string $uid): ?FieldInterface
    {
        return $this->getFieldCollection($submission)->getByUid($uid);
    }

    public function getPersistedFields(Submission $submission): array
    {
        return $this->getFieldCollection($submission)->persisted();
    }

    public function getPersistedFieldByUid(Submission $submission, string $uid): ?FieldInterface
    {
        return $this->getFieldCollection($submission)->getPersistedByUid($uid);
    }

    public function getPersistedFieldUids(Submission $submission): array
    {
        return $this->getFieldCollection($submission)->persistedUids();
    }

    public function getOrphanedValues(Submission $submission): array
    {
        return $submission->getContentState()->orphanedValuesByUid;
    }

    public function hasOrphanedValues(Submission $submission): bool
    {
        return $this->getOrphanedValues($submission) !== [];
    }

    public function setValue(Submission $submission, string $fieldHandle, mixed $value): void
    {
        $this->_accessor->setRawValue($submission, $fieldHandle, $value);
    }

    public function getValue(Submission $submission, string $fieldHandle): mixed
    {
        return $this->_accessor->getNormalizedValue($submission, $fieldHandle);
    }

    public function cloneValue(Submission $submission, string $fieldHandle): mixed
    {
        return $this->_accessor->cloneNormalizedValue($submission, $fieldHandle);
    }

    public function setRawValue(Submission $submission, string $fieldPath, mixed $value): void
    {
        $this->_accessor->setRawValue($submission, $fieldPath, $value);
    }

    public function getNormalizedValue(Submission $submission, string $fieldPath): mixed
    {
        return $this->_accessor->getNormalizedValue($submission, $fieldPath);
    }

    public function setNormalizedValue(Submission $submission, string $fieldHandle, mixed $value): void
    {
        $this->_accessor->setNormalizedValue($submission, $fieldHandle, $value);
    }

    public function getPathValue(Submission $submission, string $fieldPath): mixed
    {
        return $this->_accessor->getPathValue($submission, $fieldPath);
    }

    public function resolvePathValue(mixed $fieldValue, ?string $path): mixed
    {
        return $this->_accessor->resolvePathValue($fieldValue, $path);
    }

    public function splitFieldPath(string $fieldPath): array
    {
        return $this->_accessor->splitFieldPath($fieldPath);
    }

    public function getFieldValue(Submission $submission, string $fieldPath): mixed
    {
        return $this->getProjectedFieldValue($submission, $fieldPath, null);
    }

    public function getProjectedFieldValue(Submission $submission, string $fieldPath, ?FieldValueProjectionContext $context = null): mixed
    {
        if ($this->_looksLikeReferenceToken($fieldPath)) {
            // Resolve full `{field:...}` expressions before splitting on dots so
            // selectors, defaults, and transformers keep their authored meaning.
            $resolved = Variables::getFieldAndValueForReference($fieldPath, $submission);
            $field = $resolved['field'];
            $value = $resolved['value'];

            if ($context !== null && $field) {
                return $this->projectValue($submission, $field, $value, $context);
            }

            return $value;
        }

        [$handle, $nestedPath] = $this->splitFieldPath($fieldPath);

        if ($nestedPath !== null) {
            $value = $this->getPathValue($submission, $fieldPath);
            $field = $this->getFieldByHandle($submission, $handle);
            $segments = explode('.', $nestedPath);

            while ($field instanceof ParentFieldInterface && $segments) {
                $row = $field instanceof RepeatableParentFieldInterface ? array_shift($segments) : null;
                $childHandle = array_shift($segments);
                $child = null;

                foreach ($field->getFields($row) as $candidate) {
                    if ($candidate->handle === $childHandle) {
                        $child = $candidate;
                        break;
                    }
                }

                if (!$child) {
                    $field = null;
                    break;
                }
                $field = $child;
            }
            return $context !== null && $field && !$segments ? $this->projectValue($submission, $field, $field->normalizeFieldValue($value, $submission), $context) : $value;
        }

        $fieldValue = $this->getNormalizedValue($submission, $handle);

        if ($context !== null && ($field = $this->getFieldByHandle($submission, $handle))) {
            return $this->projectValue($submission, $field, $fieldValue, $context);
        }

        return $fieldValue;
    }

    public function getFieldValuesForField(Submission $submission, string $type): array
    {
        $fieldValues = [];

        // Return all values for a field for a given type, including nested Group/Repeater fields.
        foreach ($this->getFieldCollection($submission)->all() as $field) {
            if ($field instanceof $type) {
                $fieldValues[$field->handle] = $this->getFieldValue($submission, $field->handle);
            }

            if (!($field instanceof ParentFieldInterface)) {
                continue;
            }

            $typedNestedFields = [];

            foreach ($field->getFields() as $nestedField) {
                if ($nestedField instanceof $type) {
                    $typedNestedFields[] = $nestedField;
                }
            }

            if (!$typedNestedFields) {
                continue;
            }

            if (!($field instanceof RepeatableParentFieldInterface)) {
                foreach ($typedNestedFields as $nestedField) {
                    $fieldKey = "$field->handle.$nestedField->handle";
                    $fieldValues[$fieldKey] = $this->getFieldValue($submission, $fieldKey);
                }

                continue;
            }

            $value = $this->getFieldValue($submission, $field->handle);

            if (!is_iterable($value)) {
                continue;
            }

            foreach ($value as $rowKey => $_rowValue) {
                foreach ($typedNestedFields as $nestedField) {
                    $fieldKey = "$field->handle.$rowKey.$nestedField->handle";
                    $fieldValues[$fieldKey] = $this->getFieldValue($submission, $fieldKey);
                }
            }
        }

        return $fieldValues;
    }

    public function getValues(Submission $submission, mixed $page = null): array
    {
        $values = [];
        $form = $submission->getForm();

        if (!$form) {
            return $values;
        }

        $fields = $page ? $page->getFields() : $form->getFields();

        foreach ($fields as $field) {
            $values[$field->handle] = $this->getFieldValue($submission, $field->handle);
        }

        return $values;
    }

    public function getValuesAsString(Submission $submission): array
    {
        return $this->_getNonCosmeticProjectedValues($submission, FieldValueProjectionContext::string());
    }

    public function getValuesAsData(Submission $submission): array
    {
        return $this->_getNonCosmeticProjectedValues($submission, FieldValueProjectionContext::data());
    }

    public function getValuesForExport(Submission $submission): array
    {
        $values = [];

        foreach ($this->getFieldCollection($submission)->nonCosmetic() as $field) {
            $valueForExport = $this->getProjectedFieldValue($submission, $field->handle, FieldValueProjectionContext::export());

            // Some fields emit multiple export columns as keyed arrays.
            if (is_array($valueForExport)) {
                $values = array_merge($values, $valueForExport);
            } else {
                $values[$field->getExportLabel($submission)] = $valueForExport;
            }
        }

        return $values;
    }

    public function getValuesForSummary(Submission $submission): array
    {
        $items = [];

        foreach ($this->getFieldCollection($submission)->nonCosmetic() as $field) {
            if ($field->getIsHidden() || $field->isConditionallyHidden($submission)) {
                continue;
            }

            $value = $this->getFieldValue($submission, $field->handle);
            $html = $this->getProjectedFieldValue($submission, $field->handle, FieldValueProjectionContext::summary());

            $items[] = [
                'field' => $field,
                'value' => $value,
                'html' => $html,
            ];
        }

        return $items;
    }

    public function setFieldValuesFromRequest(Submission $submission, string $paramNamespace = ''): void
    {
        $this->normalizeFromRequest($submission, $paramNamespace);
        (new \verbb\formie\services\RuntimeConfiguration())->applyValues($submission);

        // Authoritative clearing occurs after all values are applied in the workflow.
    }

    public function setFieldValueFromRequest(Submission $submission, string $fieldHandle, mixed $value): void
    {
        $settings = Formie::$plugin->getSettings();
        $field = $this->getFieldByHandle($submission, $fieldHandle);

        if (!$field) {
            $this->normalizeSingleFromRequest($submission, $fieldHandle, $value);
            return;
        }

        // For large forms, optionally only mutate values for current-page fields.
        if ($settings->setOnlyCurrentPagePayload) {
            $currentPageFieldHandles = $this->_currentPageFieldHandles($submission);

            if ($currentPageFieldHandles && !isset($currentPageFieldHandles[$fieldHandle])) {
                return;
            }
        }

        if ($settings->setOnlyCurrentPagePayload) {
            // Merge through the owning field before normalization fills missing children.
            // Ciphertext envelopes are atomic storage values, never mergeable request maps.
            $previous = $this->getFieldValue($submission, $fieldHandle);
            $value = $field->mergePartialRequestValue($previous, $value);
        }
        $this->normalizeSingleFromRequest($submission, $fieldHandle, $value);
    }

    public function normalizeFromRequest(Submission $submission, string $paramNamespace = 'fields'): void
    {
        $this->_normalizer->normalizeFromRequest($submission, $paramNamespace);
    }

    public function normalizeSingleFromRequest(Submission $submission, string $fieldHandle, mixed $value): void
    {
        $this->_normalizer->normalizeSingleFromRequest($submission, $fieldHandle, $value);
    }

    public function normalizeFromDb(Submission $submission, null|string|array $content): void
    {
        $this->_normalizer->normalizeFromDb($submission, $content);
    }

    public function serializeForDb(Submission $submission): array
    {
        return $this->_serializer->serializeForDb($submission);
    }

    public function projectValue(Submission $submission, FieldInterface $field, mixed $value, FieldValueProjectionContext $context): mixed
    {
        return $this->_projector->projectValue($submission, $field, $value, $context);
    }


    // Private Methods
    // =========================================================================

    private function _currentPageFieldHandles(Submission $submission): array
    {
        $form = $submission->getForm();
        $currentPage = $form?->getCurrentPage();

        if (!$currentPage) {
            return [];
        }

        $pageId = (int)($currentPage->id ?? 0);
        $state = $submission->getContentState();

        if ($pageId && array_key_exists($pageId, $state->currentPageFieldHandleMapsByPageId)) {
            return $state->currentPageFieldHandleMapsByPageId[$pageId];
        }

        // Partial-page submission mode can call this repeatedly while hydrating many field payloads
        // for the same active page. Cache a handle map by page ID so the page traversal happens once,
        // and membership checks become O(1) `isset()` probes instead of repeated list scans.
        $handleMap = array_fill_keys(ArrayHelper::getColumn($currentPage->getFields(), 'handle'), true);

        if ($pageId) {
            $state->currentPageFieldHandleMapsByPageId[$pageId] = $handleMap;
        }

        return $handleMap;
    }

    private function _looksLikeReferenceToken(string $value): bool
    {
        $trimmed = trim($value);

        return str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}') && substr_count($trimmed, '{') === 1 && substr_count($trimmed, '}') === 1;
    }

    private function _getNonCosmeticProjectedValues(Submission $submission, FieldValueProjectionContext $context): array
    {
        $values = [];

        foreach ($this->getFieldCollection($submission)->nonCosmetic() as $field) {
            $values[$field->handle] = $this->getProjectedFieldValue($submission, $field->handle, $context);
        }

        return $values;
    }


}
