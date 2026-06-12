<?php
namespace verbb\formie\events;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResponse;

use yii\base\Event;
use Throwable;

class TriggerIntegrationFailureEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Submission $submission = null;
    public ?Integration $integration = null;
    public ?Throwable $exception = null;
    public IntegrationResponse|array|null $integrationResponse = null;
    public mixed $payload = null;
    public ?int $queueJobId = null;
    public bool $fromQueue = false;
}
