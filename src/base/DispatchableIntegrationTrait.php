<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

trait DispatchableIntegrationTrait
{
    // Public Methods
    // =========================================================================

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
