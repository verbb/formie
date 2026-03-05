<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyEmailDomainsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $domains = [];

}
