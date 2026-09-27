<?php
namespace verbb\formie\compatibility\integrations;

use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\IntegrationResult;

final class IntegrationResultCompatibility
{
    // Static Methods
    // =========================================================================

    public static function normalize(mixed $value, bool $uncertain = false): IntegrationResult
    {
        if ($value instanceof IntegrationResult) {
            return $value;
        }
        if ($uncertain) {
            return IntegrationResult::unknown();
        }
        if ($value instanceof IntegrationResponse) {
            $value = $value->success;
        }

        // Only documented stable returns have meaning. Objects, null and arbitrary
        // response arrays cannot become success through PHP truthiness.
        return match ($value) {
            true => IntegrationResult::succeeded(),
            false => IntegrationResult::failed('legacy_failure'),
            default => IntegrationResult::unknown('invalid_provider_result'),
        };
    }
}
