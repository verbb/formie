<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use yii\base\Event;

class ModifyDateTimeFormatEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldInterface $field = null;
    public ?string $dateFormat = null;
    public ?string $timeFormat = null;
    
}
