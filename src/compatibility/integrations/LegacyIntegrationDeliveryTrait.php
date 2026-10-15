<?php
namespace verbb\formie\compatibility\integrations;

use verbb\formie\base\DispatchableIntegrationInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

use ReflectionMethod;

trait LegacyIntegrationDeliveryTrait
{
    // Static Methods
    // =========================================================================

    /** @internal Stable provider compatibility used by the integration lifecycle. */
    public static function hasLegacyPayloadOverride(): bool
    {
        return (new ReflectionMethod(static::class, 'sendPayload'))->getDeclaringClass()->getName() !== Integration::class;
    }


    // Properties
    // =========================================================================

    protected bool $_executingLegacyPayload = false;

    private ?IntegrationResponse $_legacyIntegrationResponse = null;
    private ?IntegrationResult $_legacyParentResult = null;
    private bool $_legacyParentWriteAccepted = false;


    // Public Methods
    // =========================================================================

    /**
     * @deprecated Provider overrides use executePayload(). Application callers use IntegrationTriggers::runManualIntegration()
     * for permissioned manual execution, or the submission workflow for automatic dispatch.
     */
    public function sendPayload(Submission $submission)
    {
        $previous = $this->_executingLegacyPayload;
        $this->_executingLegacyPayload = true;

        try {
            $result = $this instanceof DispatchableIntegrationInterface
                ? $this->execute($previous && $this->_runContext?->submission === $submission ? $this->_runContext : IntegrationRunContext::forIntegration($this, $submission))
                : IntegrationResult::rejected('integration_not_dispatchable');
            $this->_legacyParentResult = $result;
            $this->_legacyParentWriteAccepted = $this->_deliveryState->writeAccepted;

            return IntegrationResultCompatibility::toLegacy($result);
        } finally {
            $this->_executingLegacyPayload = $previous;
        }
    }

    /** @internal Stable provider compatibility used by the integration lifecycle. */
    public function executeLegacyPayload(IntegrationRunContext $context): IntegrationResult
    {
        $this->beginRun($context);

        if (!static::hasLegacyPayloadOverride()) {
            return IntegrationResult::rejected('integration_not_dispatchable');
        }
        unset($this->context['deliveryErrorResult'], $this->context['deliverySkipped'], $this->context['deliveryWriteAccepted'], $this->context['deliveryUncertain'], $this->context['dispatchElement']);
        $this->_legacyIntegrationResponse = null;
        $this->_legacyParentResult = null;
        $this->_legacyParentWriteAccepted = false;
        $previous = $this->_executingLegacyPayload;
        $this->_executingLegacyPayload = true;

        try {
            $value = $this->sendPayload($context->submission);
        } finally {
            $this->_executingLegacyPayload = $previous;
        }
        $this->_legacyIntegrationResponse = $value instanceof IntegrationResponse ? $value : null;
        // Only stable third-party adapters may import the old mutable channels.
        $state = $context->state;
        $state->error ??= $this->context['deliveryErrorResult'] ?? null;
        $state->skipped = $state->skipped || !empty($this->context['deliverySkipped']);
        $state->writeAccepted = $state->writeAccepted || !empty($this->context['deliveryWriteAccepted']);
        $state->uncertain = $state->uncertain || !empty($this->context['deliveryUncertain']);
        $state->outputs += (array)($this->context['dispatchElement'] ?? []);
        // Preserve native certainty, outputs and retry details through a legacy parent return.
        $parentResult = $this->_legacyParentResult;
        $legacySuccess = $value instanceof IntegrationResponse ? $value->success : $value;

        if ($parentResult) {
            // Native writes are already covered by the parent's certainty and retry policy.
            // A later legacy write or uncertainty must not inherit that earlier outcome.
            $legacyWriteAccepted = ($state->writeAccepted && !$this->_legacyParentWriteAccepted) || !empty($this->context['deliveryWriteAccepted']);

            if (!$parentResult->requiresReconciliation() && !$state->error?->requiresReconciliation() && ($state->uncertain || ($legacySuccess === false && $legacyWriteAccepted))) {
                $state->error = IntegrationResult::unknown()->withOutputs($parentResult->outputs);
            }

            if ($parentResult->requiresReconciliation() || $legacySuccess === IntegrationResultCompatibility::toLegacy($parentResult)) {
                $value = $parentResult;
            }
        }

        return $this->resultForPayload($value);
    }

    /** @internal Stable provider compatibility used by the integration lifecycle. */
    public function getLegacyIntegrationResponse(): ?IntegrationResponse
    {
        return $this->_legacyIntegrationResponse;
    }
}
