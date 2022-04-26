<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFrontEndSubfieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $field = null;
    public mixed $rows = null;
    
}
