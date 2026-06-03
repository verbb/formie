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
        if (is_string($content) && Json::isJsonObject($content)) {
            $content = Json::decode($content);
        }

        if (!$content || !is_array($content)) {
            return;
        }

        $this->normalizeDbPayload($submission, $content);
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
