<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterClientEventTemplatesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $templates = [];
}
