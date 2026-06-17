<?php
namespace verbb\formie\events;

use verbb\formie\models\Report;

use yii\base\Event;

class ReportEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Report $report = null;
    public bool $isNew = false;
}
