<?php
namespace verbb\formie\deprecations;

use verbb\formie\elements\Submission;

use Craft;

trait SubmissionContentManagerDeprecations
{
    // Public Methods
    // =========================================================================

    public function getValuesAsJson(Submission $submission): array
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Submission content manager `getValuesAsJson()` has been deprecated. Use `getValuesAsArray()` instead.');

        return $this->getValuesAsArray($submission);
    }
}
