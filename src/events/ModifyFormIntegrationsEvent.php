<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;

use yii\base\Event;

class ModifyFormIntegrationsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $allIntegrations = null;
    public ?array $integrations = null;
    public ?Form $form = null;
    
}
