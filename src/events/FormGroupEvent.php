<?php
namespace verbb\formie\events;

use verbb\formie\models\FormGroup;

use yii\base\Event;

class FormGroupEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormGroup $formGroup = null;
    public bool $isNew = false;
}
