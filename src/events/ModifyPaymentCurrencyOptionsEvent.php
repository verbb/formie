<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyPaymentCurrencyOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $currencies = null;
}
