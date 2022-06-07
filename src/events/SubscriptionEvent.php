<?php
namespace verbb\formie\events;

use verbb\formie\models\Subscription;

use yii\base\Event;

class SubscriptionEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Subscription $subscription = null;
    public bool $isNew = false;
    
}
