<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyAddressProviderHtmlEvent extends Event
{
    // Properties
    // =========================================================================

    public ?string $html = null;
    public ?string $js = null;
    
}
