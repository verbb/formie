<?php
namespace verbb\formie\events;

use yii\base\Event;

class EmailTemplateEvent extends Event
{
    // Properties
    // =========================================================================

    public ?string $template = null;
    public bool $isNew = false;
    
}
