<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyBrowserJsTranslationsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $strings = [];
}
