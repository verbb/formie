<?php
namespace verbb\formie\events;

use verbb\formie\base\FormField;

use yii\base\Event;

class SyncedFieldEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormField $field = null;
    
}
