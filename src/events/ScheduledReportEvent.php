<?php
namespace verbb\formie\events;

use verbb\formie\models\ScheduledReport;

use yii\base\Event;

class ScheduledReportEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ScheduledReport $scheduledReport = null;
    public bool $isNew = false;
}
