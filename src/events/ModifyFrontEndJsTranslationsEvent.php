<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFrontEndJsTranslationsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $strings = [];
    
}
