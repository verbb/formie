<?php
namespace verbb\formie\events;

use yii\base\Event;

class EmailTemplateEvent extends Event
{
    // Properties
    // =========================================================================

    public $template;
    public $isNew = false;
    
}
