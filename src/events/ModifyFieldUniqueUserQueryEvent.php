<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;

use craft\base\ElementInterface;
use craft\elements\db\UserQuery;

use yii\base\Event;

class ModifyFieldUniqueUserQueryEvent extends Event
{
    // Properties
    // =========================================================================

    public ?UserQuery $query = null;
    public ?FieldInterface $field = null;
    public ?ElementInterface $element = null;
}
