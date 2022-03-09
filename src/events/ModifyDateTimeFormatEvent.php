<?php
namespace verbb\formie\events;

use yii\base\Event;
use verbb\formie\base\FormField;

class ModifyDateTimeFormatEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormField $field = null;
    public ?string $dateFormat = null;
    public ?string $timeFormat = null;
    
}
