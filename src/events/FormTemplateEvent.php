<?php
namespace verbb\formie\events;

use yii\base\Event;

class FormTemplateEvent extends Event
{
    // Properties
    // =========================================================================

    public $template;
    public $isNew = false;

}
