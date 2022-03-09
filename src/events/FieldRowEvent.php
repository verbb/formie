<?php
namespace verbb\formie\events;

use verbb\formie\records\Row;

use yii\base\Event;

class FieldRowEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Row $row = null;
    public bool $isNew = false;
    
}
