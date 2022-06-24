<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyElementFieldQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public $query;
    public $field;

}
