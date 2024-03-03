<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use craft\elements\db\ElementQueryInterface;

use yii\base\Event;

class ModifyElementFieldQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ElementQueryInterface $query = null;
    public ?FieldInterface $field = null;
    
}
