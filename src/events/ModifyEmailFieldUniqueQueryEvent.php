<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;

use craft\db\Query;

use yii\base\Event;

class ModifyEmailFieldUniqueQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Query $query = null;
    public ?FormFieldInterface $field = null;
    
}
