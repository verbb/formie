<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterTransformersEvent extends Event
{
    // Properties
    // =========================================================================

    public array $transformerRegistry = [];
}
