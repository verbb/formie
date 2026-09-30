<?php
namespace verbb\formie\base;

use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationRunContext;

interface DispatchableIntegrationInterface extends IntegrationInterface
{
    public function execute(IntegrationRunContext $context): IntegrationResult;
}
