<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldExportEvent extends Event
{
    // Properties
    // =========================================================================

    public $field;
    public $value;
    public $element;
    public $fieldValue;
    
}
