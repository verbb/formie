<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyPhoneCountriesEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $countries = null;
    
}
