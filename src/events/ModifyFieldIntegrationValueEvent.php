<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldIntegrationValueEvent extends Event
{
    // Properties
    // =========================================================================

    public $value;
    public $field;
    public $submission;
    public $integrationField;
    public $integration;
    
}
