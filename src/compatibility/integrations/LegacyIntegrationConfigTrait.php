<?php
namespace verbb\formie\compatibility\integrations;

use verbb\formie\base\Integration;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationFormSettings;

trait LegacyIntegrationConfigTrait
{
    // Public Methods
    // =========================================================================

    /** @deprecated Use fetchConfig() for provider metadata acquisition. */
    public function fetchFormSettings()
    {
        return $this->callCanonicalIntegrationMethod('fetchFormSettings', fn() => IntegrationFormSettings::fromConfig($this->fetchConfig()));
    }


    // Protected Methods
    // =========================================================================

    protected function fetchConfigWithCompatibility(): IntegrationConfig
    {
        return $this->callLegacyIntegrationMethod(
            'fetchFormSettings',
            Integration::class,
            [],
            fn() => $this->fetchConfig(),
            fn($settings) => $settings instanceof IntegrationFormSettings ? $settings->toConfig() : $this->fetchConfig(),
            'fetchConfig',
        );
    }
}
