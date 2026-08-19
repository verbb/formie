<?php
namespace verbb\formie\helpers;

use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

use Craft;

/**
 * How an editExisting save should behave.
 *
 * REVISION — save changes to this submission as a saved record. Do not treat
 * the POST like a visitor clicking Next on a multi-page form (CP always; front-end
 * when the submission is already complete).
 *
 * CONTINUATION — front-end only, while the submission is still incomplete:
 * resolve the next page, incomplete vs complete, and first-time finish status.
 */
class SubmissionEditBehaviour
{
    // Constants
    // =========================================================================

    public const REVISION = 'revision';
    public const CONTINUATION = 'continuation';


    // Public Methods
    // =========================================================================

    public static function resolve(SubmissionRequest $request): ?string
    {
        if ($request->processMode !== SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING) {
            return null;
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            return self::REVISION;
        }

        if ($request->submission->id && !$request->submission->isIncomplete) {
            return self::REVISION;
        }

        return self::CONTINUATION;
    }

    public static function isRevision(?string $behaviour): bool
    {
        return $behaviour === self::REVISION;
    }

    public static function isContinuation(?string $behaviour): bool
    {
        return $behaviour === self::CONTINUATION;
    }
}
