<?php
namespace verbb\formie\events;

use verbb\formie\models\FormStatus;

use yii\base\Event;

class FormStatusEvent extends Event
{
    // Properties
    // =========================================================================

    public ?FormStatus $status = null;
    public bool $isNew = false;
}
