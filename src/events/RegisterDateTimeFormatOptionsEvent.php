<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use yii\base\Event;

class RegisterDateTimeFormatOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldInterface $field = null;
    public array $options = [];
    
}

class_alias(
    RegisterDateTimeFormatOptionsEvent::class,
    RegisterDateTimeFormatOpionsEvent::class
);

