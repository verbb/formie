<?php
namespace verbb\formie\base;

use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

interface DispatchableIntegrationInterface extends IntegrationInterface
{
    // Public Methods
    // =========================================================================

    public function execute(IntegrationRunContext $context): IntegrationResult;
}
