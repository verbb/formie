<?php
namespace verbb\formie\content;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Checkboxes;

use Craft;
use craft\errors\InvalidFieldException;
use craft\helpers\Json;
use craft\web\UploadedFile;

class SubmissionContentNormalizer
{
    // Public Methods
    // =========================================================================

    public function normalizeFromRequest(Submission $submission, string $paramNamespace = 'fields'): void
    {
        if ($paramNamespace) {
            $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);
        } else {
            $values = Craft::$app->getRequest()->getBodyParams();
        }

        $this->normalizeRequestPayload($submission, is_array($values) ? $values : [], $paramNamespace);
    }

    public function normalizeRequestPayload(Submission $submission, array $values, string $paramNamespace = 'fields'): void
    {
        $manager = $submission->getContentManager();
        $fileFieldsByHandle = $paramNamespace ? $manager->getFieldCollection($submission)->fileFieldsByHandle() : [];

        foreach ($manager->getFieldCollection($submission)->all() as $field) {
            if (array_key_exists($field->handle, $values)) {
                $value = $values[$field->handle];
            } else if ($paramNamespace && isset($fileFieldsByHandle[$field->handle]) && UploadedFile::getInstancesByName("$paramNamespace.$field->handle")) {
                // File uploads may have no scalar body param at all; treat the
                // field as present so `normalizeValueFromRequest()` can inspect
                // the uploaded file instances itself.
                $value = null;
            } else if ($paramNamespace && $this->_shouldTreatMissingCheckboxesAsEmpty($submission, $field)) {
                $value = [];
            } else {
                continue;
            }

            $normalized = $field->normalizeValueFromRequest($value, $submission);
            $manager->setNormalizedValue($submission, $field->handle, $normalized);
        }
    }

    public function normalizeSingleFromRequest(Submission $submission, string $fieldHandle, mixed $value): void
    {
        $field = $submission->getContentManager()->getFieldByHandle($submission, $fieldHandle);

        if (!$field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $normalized = $field->normalizeValueFromRequest($value, $submission);
        $submission->getContentManager()->setNormalizedValue($submission, $field->handle, $normalized);
    }

    public function normalizeFromDb(Submission $submission, null|string|array $content): void
    {
        $content = self::decodeStoredPayload($content);

        if (!$content) {
            return;
        }

        $this->normalizeDbPayload($submission, $content);
    }

    public static function decodeStoredPayload(mixed $content): ?array
    {
        if (is_array($content)) {
            return $content;
        }

        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        // Some historical migrations wrote JSON-encoded strings into the JSON
        // column, so decode repeatedly until we reach the uid-keyed payload.
        while (is_string($content)) {
            try {
                $decoded = Json::decode($content);
            } catch (\Throwable) {
                return null;
            }

            if (is_array($decoded)) {
                return $decoded;
            }

            if (!is_string($decoded) || $decoded === $content) {
                return null;
            }

            $content = $decoded;
        }

        return null;
    }

    public function normalizeDbPayload(Submission $submission, array $content): void
    {
        $manager = $submission->getContentManager();
        $submission->getContentState()->orphanedValuesByUid = [];

        foreach ($content as $uid => $value) {
            if (!is_string($uid) && !is_int($uid)) {
                continue;
            }

            $field = $manager->getPersistedFieldByUid($submission, (string)$uid);

            if (!$field) {
                // Keep unresolved content around so editing/resaving a submission
                // does not silently erase values from an older field structure.
                $submission->getContentState()->orphanedValuesByUid[(string)$uid] = $value;
                continue;
            }

            $manager->setRawValue($submission, $field->handle, $value);
        }
    }


    // Private Methods
    // =========================================================================

    private function _shouldTreatMissingCheckboxesAsEmpty(Submission $submission, FieldInterface $field): bool
    {
        if (!$field instanceof Checkboxes) {
            return false;
        }

        $currentPage = $submission->getForm()?->getCurrentPage();

        if (!$currentPage || !method_exists($field, 'getPage')) {
            return true;
        }

        $fieldPage = $field->getPage();

        return $fieldPage && (int)$fieldPage->id === (int)$currentPage->id;
    }
}
