<?php
namespace verbb\formie\events;

use craft\elements\db\ElementQueryInterface;

use yii\base\Event;

class ModifySubmissionExportDataEvent extends Event
{
    // Properties
    // =========================================================================

    public array $exportData;
    public ElementQueryInterface $query;
    
}
