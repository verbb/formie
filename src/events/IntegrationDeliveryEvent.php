<?php
namespace verbb\formie\events;

use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;

use yii\base\Event;

class IntegrationDeliveryEvent extends Event
{
    // Properties
    // =========================================================================

    public IntegrationExecutionContext $context;
    public ?IntegrationBatchResult $batch = null;
    public ?IntegrationResult $result = null;
    public ?string $attemptUid = null;
}
