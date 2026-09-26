<?php
namespace verbb\formie\errors;

use yii\web\ConflictHttpException;

/** A stale progress writer; unavailable continuation is a different failure. */
class StateConflict extends ConflictHttpException
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly ?int $currentVersion = null)
    {
        parent::__construct('This form changed in another tab.');
    }
}
