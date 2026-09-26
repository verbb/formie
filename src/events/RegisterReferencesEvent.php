<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterReferencesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $sources = [];
    public array $transforms = [];
}
