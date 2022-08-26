<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifySubmissionExportDataEvent extends Event
{
    // Properties
    // =========================================================================

    public $exportData;
    public $query;
    
}
