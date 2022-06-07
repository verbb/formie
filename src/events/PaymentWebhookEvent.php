<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;

use yii\base\Event;
use yii\web\Response;

class PaymentWebhookEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Integration $integration = null;
    public ?Response $response = null;
}
