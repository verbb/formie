<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

trait DispatchableIntegrationTrait
{
    // Public Methods
    // =========================================================================

    /** Built-in HTTP providers route every side effect through executeDeliveryWrite(). */
    public function supportsDeliveryWriteTracking(): bool
    {
        // Third-party implementations and subclasses must explicitly opt in;
        // their payload or transport overrides may bypass the guarded writes.
        return str_starts_with(static::class, 'verbb\\formie\\integrations\\') && !static::hasLegacyPayloadOverride();
    }

    /** Runner-facing lifecycle; providers normally implement executePayload(). */
    public function execute(IntegrationRunContext $context): IntegrationResult
    {
        $this->beginRun($context);

        if (static::hasLegacyPayloadOverride() && !$this->_executingLegacyPayload) {
            return $this->executeLegacyPayload($context);
        }
        return $this->executePayload($context->submission);
    }


    // Protected Methods
    // =========================================================================

    protected function executePayload(Submission $submission): IntegrationResult
    {
        return IntegrationResult::rejected('payload_handler_missing');
    }
}
