<?php
namespace verbb\formie\events;

use craft\models\FieldLayout;

use yii\base\Event;

class ModifyElementFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldLayout $fieldLayout = null;
    public array $fields = [];
    
}
