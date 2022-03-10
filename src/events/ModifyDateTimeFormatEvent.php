<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;

use yii\base\Event;

class ModifyDateTimeFormatEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormFieldInterface $field = null;
    public ?string $dateFormat = null;
    public ?string $timeFormat = null;
    
}
