<?php
namespace verbb\formie\events;

use craft\base\ElementInterface;
use craft\events\ModelEvent;

class FieldElementEvent extends ModelEvent
{
    // Properties
    // =========================================================================

    public ElementInterface $element;
}
