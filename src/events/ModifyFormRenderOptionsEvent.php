<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFormRenderOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public $form;
    public $options;

}
