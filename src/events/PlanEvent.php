<?php
namespace verbb\formie\events;

use verbb\formie\models\Plan;

use yii\base\Event;

class PlanEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Plan $plan = null;
    public bool $isNew = false;
    
}
