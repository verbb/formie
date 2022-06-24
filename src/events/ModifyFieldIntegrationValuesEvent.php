<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyFieldIntegrationValuesEvent extends Event
{
    // Properties
    // =========================================================================

    public $fieldValues;
    public $submission;
    public $fieldMapping;
    public $fieldSettings;
    public $integration;

}
