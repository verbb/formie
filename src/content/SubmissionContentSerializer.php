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

        foreach ($manager->getPersistedFieldUids($submission) as $fieldUid) {
            $field = $manager->getPersistedFieldByUid($submission, $fieldUid);

            if (!$field) {
                continue;
            }

            $serializedValue = $field->serializeValue($manager->getNormalizedValue($submission, $field->handle), $submission);
            $content[$field->uid] = $serializedValue;
        }

        // Strip null/empty branches after serialization so fields control what
        // "meaningfully empty" means during serialization, but the stored payload
        // does not accumulate empty structural noise.
        $content = ArrayHelper::filterNull($content);

        return ArrayHelper::recursiveFilter($content, function($value): bool {
            return $value !== [];
        });
    }
}
