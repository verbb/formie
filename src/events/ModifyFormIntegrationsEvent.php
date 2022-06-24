<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFormIntegrationsEvent extends Event
{
    // Properties
    // =========================================================================

    public $integrations;
    public $type;

}
