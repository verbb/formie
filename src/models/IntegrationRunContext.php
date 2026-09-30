<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\DeliveryAttempt;

use craft\helpers\StringHelper;

/** Explicit input and execution state for one integration operation. */
final readonly class IntegrationRunContext
{
    // Static Methods
    // =========================================================================

    public static function forIntegration(Integration $integration, Submission $submission): self
    {
        $execution = $integration->getDeliveryExecutionContext() ?? new IntegrationExecutionContext(
            (int)$submission->id, (int)$submission->formId, (string)$integration->handle,
            DeliveryAttempt::workflowIdentity() ?? StringHelper::UUID(), 'synchronous', 'direct',
        );
        return new self($submission, $execution, $integration->getDeliveryAttemptUid(),
            Formie::$plugin->getIntegrationDispatcher()->loadContext($submission, $execution->executionUid));
    }


    // Properties
    // =========================================================================

    public IntegrationDeliveryState $state;


    // Public Methods
    // =========================================================================

    public function __construct(
        public Submission $submission,
        public IntegrationExecutionContext $execution,
        public ?string $attemptUid = null,
        public IntegrationRunResults $previousResults = new IntegrationRunResults(),
    ) {
        $this->state = new IntegrationDeliveryState();
    }
}
