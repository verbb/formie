<?php
namespace verbb\formie\events;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;

use yii\base\Event;

class ModifyFieldIntegrationValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public mixed $rawValue = null;
    public ?FieldInterface $field = null;
    public ?Submission $submission = null;
    public ?IntegrationField $integrationField = null;
    public ?Integration $integration = null;
    
}
