<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use yii\base\Event;

class ModifyAddressSubdivisionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldInterface $field = null;
    public string $countryCode = '';
    public array $subdivisions = [];
}
