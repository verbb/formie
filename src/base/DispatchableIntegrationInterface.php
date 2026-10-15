<?php
namespace verbb\formie\base;

use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

/** Execution capability for Integration subclasses. Applications invoke the integration trigger services. */
interface DispatchableIntegrationInterface extends IntegrationInterface
{
    // Public Methods
    // =========================================================================

    public function execute(IntegrationRunContext $context): IntegrationResult;
}
