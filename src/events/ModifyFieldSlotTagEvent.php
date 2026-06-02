<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;
use verbb\formie\models\SlotTag;

use yii\base\Event;

class ModifyFieldSlotTagEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FieldInterface $field = null;
    public ?SlotTag $tag = null;
    public ?string $key = null;
    public ?array $context = null;
}
