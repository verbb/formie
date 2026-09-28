<?php
namespace verbb\formie\events;

use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\models\Subscription;
use verbb\formie\models\payments\SubscriptionSnapshot;

use yii\base\Event;

class SubscriptionEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Subscription $subscription = null;
    public bool $isNew = false;
    public ?SubscriptionStatus $previousStatus = null;
    public ?SubscriptionStatus $currentStatus = null;
    public ?SubscriptionSnapshot $snapshot = null;
    public ?string $source = null;
}
