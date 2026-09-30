<?php
namespace verbb\formie\events;

use verbb\formie\models\SubscriptionPlan;

use yii\base\Event;

class PlanEvent extends Event
{
    // Properties
    // =========================================================================

    public ?SubscriptionPlan $plan = null;
    public bool $isNew = false;
    
}
