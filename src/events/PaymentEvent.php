<?php
namespace verbb\formie\events;

use verbb\formie\models\Payment;

use yii\base\Event;

class PaymentEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Payment $payment = null;
    public bool $isNew = false;
    
}
