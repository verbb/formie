<?php
namespace verbb\formie\events;

use yii\base\Event;

class StencilEvent extends Event
{
    // Properties
    // =========================================================================

    public $stencil;
    public $isNew = false;

}
