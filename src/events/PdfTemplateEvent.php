<?php
namespace verbb\formie\events;

use yii\base\Event;

class PdfTemplateEvent extends Event
{
    // Properties
    // =========================================================================

    public ?string $template = null;
    public bool $isNew = false;
    
}
