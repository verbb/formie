<?php
namespace verbb\formie\events;

use verbb\formie\models\SubmissionStatus;

use yii\base\Event;

class SubmissionStatusEvent extends Event
{
    // Properties
    // =========================================================================

    public ?SubmissionStatus $status = null;
    public bool $isNew = false;
}
