<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;
use verbb\formie\models\SlotTag;

use yii\base\Event;

class ModifyFormSlotTagEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?SlotTag $tag = null;
    public ?string $key = null;
    public ?array $context = null;
}
