<?php
namespace verbb\formie\events;

use verbb\formie\base\FormField;

use craft\elements\db\ElementQueryInterface;

use yii\base\Event;

class ModifyEmailFieldUniqueQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ElementQueryInterface $query = null;
    public ?FormField $field = null;
    
}
