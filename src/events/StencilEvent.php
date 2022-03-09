<?php
namespace verbb\formie\events;

use verbb\formie\models\Stencil;

use yii\base\Event;

class StencilEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Stencil $stencil = null;
    public bool $isNew = false;

}
