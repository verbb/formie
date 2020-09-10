<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyMigrationFieldEvent extends Event
{
    // Properties
    // =========================================================================

    public $field;
    public $newField;
    
}
