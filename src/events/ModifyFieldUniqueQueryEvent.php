<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use craft\db\Query;

use yii\base\Event;

class ModifyFieldUniqueQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Query $query = null;
    public ?FieldInterface $field = null;
    
}
