<?php
namespace verbb\formie\compatibility\payments;

trait LegacyPaymentStatus
{
    // Constants
    // =========================================================================

    /** @deprecated in 4.0.0. Redirect is an action, not a domain state. */
    public const STATUS_REDIRECT = self::STATUS_REQUIRES_ACTION;
    /** @deprecated in 4.0.0. Use STATUS_SUCCEEDED. */
    public const STATUS_SUCCESS = self::STATUS_SUCCEEDED;
}
