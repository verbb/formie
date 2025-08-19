<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use yii\base\Event;

class ModifyAddressCountriesEvent extends Event
{
    // Properties
    // =========================================================================
    
    public ?FieldInterface $field = null;
    public array $countries = [];
    
}
