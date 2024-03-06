<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFrontEndSubFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $field = null;
    public mixed $rows = null;
    
}
