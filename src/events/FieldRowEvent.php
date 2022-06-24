<?php
namespace verbb\formie\events;

use yii\base\Event;

class FieldRowEvent extends Event
{
    // Properties
    // =========================================================================

    public $row;
    public $isNew = false;

}
