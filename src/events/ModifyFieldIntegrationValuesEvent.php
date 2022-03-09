<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;

use yii\base\Event;

class ModifyFieldIntegrationValuesEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $fieldValues = null;
    public ?Submission $submission = null;
    public ?array $fieldMapping = null;
    public ?array $fieldSettings = null;
    public ?Integration $integration = null;
    
}
