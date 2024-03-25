<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyNotificationSchemaEvent extends Event
{
    // Properties
    // =========================================================================

    public array $tabs = [];
    public array $fields = [];
    
}
