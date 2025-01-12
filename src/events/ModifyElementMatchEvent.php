<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyElementMatchEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $elementType = null;
    public mixed $identifier = null;
    public mixed $submission = null;
    public mixed $criteria = null;
    public mixed $element = null;
    
}
