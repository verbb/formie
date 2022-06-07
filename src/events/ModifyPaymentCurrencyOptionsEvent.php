<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;

use yii\base\Event;

class ModifyPaymentCurrencyOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $currencies = null;

}
