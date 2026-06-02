<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\models\SlotTag;

use yii\base\Event;

class ModifyIntegrationSlotTagEvent extends Event
{
    public ?Integration $integration = null;
    public ?SlotTag $tag = null;
    public ?string $key = null;
    public ?array $context = null;
}
