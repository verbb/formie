<?php
namespace verbb\formie\events;

use verbb\formie\base\FormField;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;

use yii\base\Event;

class ModifyFieldIntegrationValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormField $field = null;
    public ?Submission $submission = null;
    public ?IntegrationField $integrationField = null;
    public ?Integration $integration = null;
    
}
