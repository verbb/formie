<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $fields = [];
}
