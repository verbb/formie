<?php
namespace verbb\formie\compatibility\payments;

use verbb\formie\helpers\SubscriptionProviderData;

trait LegacySubscriptionData
{
    // Public Methods
    // =========================================================================

    /** @deprecated in 4.0.0. Only curated providerData is exposed on the model. */
    public function getSubscriptionData(): array
    {
        return $this->providerData;
    }

    public function setSubscriptionData(?array $value): void
    {
        $this->providerData = SubscriptionProviderData::project($value ?? []);
    }
}
