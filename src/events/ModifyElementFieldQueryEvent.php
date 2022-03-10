<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;

use craft\elements\db\ElementQueryInterface;

use yii\base\Event;

class ModifyElementFieldQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ElementQueryInterface $query = null;
    public ?FormFieldInterface $field = null;
    
}
