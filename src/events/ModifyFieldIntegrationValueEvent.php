<?php
namespace verbb\formie\events;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\elements\Submission;
use verbb\formie\models\FakeElement;
use verbb\formie\models\IntegrationField;

use yii\base\Event;

class ModifyFieldIntegrationValueEvent extends Event
{
    // Properties
    // =========================================================================

    public mixed $value = null;
    public ?FormFieldInterface $field = null;
    public Submission|NestedFieldRow|FakeElement|null $submission = null;
    public ?IntegrationField $integrationField = null;
    public ?Integration $integration = null;
    
}
