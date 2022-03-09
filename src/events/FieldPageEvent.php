<?php
namespace verbb\formie\events;

use verbb\formie\records\PageSettings;

use yii\base\Event;

class FieldPageEvent extends Event
{
    // Properties
    // =========================================================================

    public ?PageSettings $page = null;
    public bool $isNew = false;
    
}
