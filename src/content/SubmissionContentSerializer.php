<?php
namespace verbb\formie\content;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

class SubmissionContentSerializer
{
    // Public Methods
    // =========================================================================

    public function serializeForDb(Submission $submission): array
    {
        $content = $submission->getContentState()->orphanedValuesByUid;
        $manager = $submission->getContentManager();
        $presentValues = [];
        $rawValues = $submission->getContentState()->rawValuesByUid;

        foreach ($manager->getPersistedFieldUids($submission) as $fieldUid) {
            $field = $manager->getPersistedFieldByUid($submission, $fieldUid);

            if (!$field) {
                continue;
            }

            $serializedValue = $field->serializeValue($manager->getNormalizedValue($submission, $field->handle), $submission);
            $content[$field->uid] = $serializedValue;
            if (array_key_exists($fieldUid, $rawValues)) {
                $presentValues[$fieldUid] = $serializedValue;
            }
        }

        // Strip null/empty branches after serialization so fields control what
        // "meaningfully empty" means during serialization, but the stored payload
        // does not accumulate empty structural noise.
        $content = ArrayHelper::filterNull($content);

        $content = ArrayHelper::recursiveFilter($content, function($value): bool {
            return $value !== [];
        });

        // A supplied value can serialize to null or an empty branch regardless
        // of its raw type. Keep that clear so historical content cannot return.
        foreach ($presentValues as $fieldUid => $serializedValue) {
            if (!array_key_exists($fieldUid, $content)) {
                $content[$fieldUid] = $serializedValue;
            }
        }

        return $content;
    }
}
