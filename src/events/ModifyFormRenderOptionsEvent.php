<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;

use yii\base\Event;

class ModifyFormRenderOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Form $form = null;
    public ?array $options = null;

}
