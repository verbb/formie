<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;
use yii\base\Event;

class RegisterFieldOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var FormFieldInterface
     */
    public $field = null;

    /**
     * @var array
     */
    public $options = [];
}
