<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFrontendJsTranslationsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $strings = [];
}
