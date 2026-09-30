<?php
namespace verbb\formie\compatibility\integrations;

use verbb\formie\base\DispatchableIntegrationInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

use ReflectionMethod;

trait LegacyIntegrationDeliveryTrait
{
    // Properties
    // =========================================================================

    protected bool $_executingLegacyPayload = false;
    private ?\verbb\formie\models\IntegrationResponse $_legacyIntegrationResponse = null;


    // Static Methods
    // =========================================================================

    public static function hasLegacyPayloadOverride(): bool
    {
        return (new ReflectionMethod(static::class, 'sendPayload'))->getDeclaringClass()->getName() !== Integration::class;
    }


    // Public Methods
    // =========================================================================

    /** @deprecated Use execute() with an IntegrationRunContext for dispatchable integrations. */
    public function sendPayload(Submission $submission)
    {
        $previous = $this->_executingLegacyPayload;
        $this->_executingLegacyPayload = true;
        try {
            return $this instanceof DispatchableIntegrationInterface
                ? $this->execute(IntegrationRunContext::forIntegration($this, $submission))
                : IntegrationResult::rejected('integration_not_dispatchable');
        } finally {
            $this->_executingLegacyPayload = $previous;
        }
    }

    public function executeLegacyPayload(IntegrationRunContext $context): IntegrationResult
    {
        $this->beginRun($context);
        if (!static::hasLegacyPayloadOverride()) {
            return IntegrationResult::rejected('integration_not_dispatchable');
        }
        unset($this->context['deliveryErrorResult'], $this->context['deliverySkipped'], $this->context['deliveryWriteAccepted'], $this->context['deliveryUncertain'], $this->context['dispatchElement']);
        $this->_legacyIntegrationResponse = null;
        $previous = $this->_executingLegacyPayload;
        $this->_executingLegacyPayload = true;
        try {
            $value = $this->sendPayload($context->submission);
        } finally {
            $this->_executingLegacyPayload = $previous;
        }
        $this->_legacyIntegrationResponse = $value instanceof \verbb\formie\models\IntegrationResponse ? $value : null;
        // Only stable third-party adapters may import the old mutable channels.
        $state = $context->state;
        $state->error ??= $this->context['deliveryErrorResult'] ?? null;
        $state->skipped = $state->skipped || !empty($this->context['deliverySkipped']);
        $state->writeAccepted = $state->writeAccepted || !empty($this->context['deliveryWriteAccepted']);
        $state->uncertain = $state->uncertain || !empty($this->context['deliveryUncertain']);
        $state->outputs += (array)($this->context['dispatchElement'] ?? []);
        return $this->resultForPayload($value);
    }

    public function getLegacyIntegrationResponse(): ?\verbb\formie\models\IntegrationResponse
    {
        return $this->_legacyIntegrationResponse;
    }
}
