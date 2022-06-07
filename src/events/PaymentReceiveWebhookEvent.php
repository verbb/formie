<?php
namespace verbb\formie\events;

use yii\base\Event;

class PaymentReceiveWebhookEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $webhookData = null;
    
}
