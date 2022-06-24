<?php
namespace verbb\formie\events;

use yii\base\Event;

class PdfTemplateEvent extends Event
{
    // Properties
    // =========================================================================

    public $template;
    public $isNew = false;

}
