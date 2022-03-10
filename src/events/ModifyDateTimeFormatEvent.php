<?php
namespace verbb\formie\events;

use yii\base\Event;
use verbb\formie\base\FormFieldInterface;

class ModifyDateTimeFormatEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormFieldInterface $field = null;
    public ?string $dateFormat = null;
    public ?string $timeFormat = null;
    
}
