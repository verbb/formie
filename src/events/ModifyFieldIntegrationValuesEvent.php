<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\models\FakeElement;

use yii\base\Event;

class ModifyFieldIntegrationValuesEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $fieldValues = null;
    public Submission|NestedFieldRow|FakeElement|null $submission = null;
    public ?array $fieldMapping = null;
    public ?array $fieldSettings = null;
    public ?Integration $integration = null;
    
}
