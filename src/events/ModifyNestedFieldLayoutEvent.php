<?php
namespace verbb\formie\events;

use verbb\formie\models\FieldLayout;

use yii\base\Event;

class ModifyNestedFieldLayoutEvent extends Event
{
    // Properties
    // =========================================================================

    public FieldLayout $fieldLayout;
    
}
