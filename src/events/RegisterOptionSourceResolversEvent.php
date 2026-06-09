<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterOptionSourceResolversEvent extends Event
{
    // Properties
    // =========================================================================

    public array $resolvers = [];
}
