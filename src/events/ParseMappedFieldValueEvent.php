<?php
namespace verbb\formie\events;

use yii\base\Event;

class ParseMappedFieldValueEvent extends Event
{
    // Properties
    // =========================================================================

    public $integrationField;
    public $formField;
    public $value;
    public $submission;
    public $integration;

}
