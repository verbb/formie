<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;

use yii\base\Event;

class RegisterFieldOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormFieldInterface $field = null;
    public array $options = [];
}
