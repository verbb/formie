<?php
namespace verbb\formie\cache;

class IntegrationLookupCache
{
    // Properties
    // =========================================================================

    public array $groupedIntegrationTypes = [];
    public array $integrationsByType = [];
    public array $integrationsById = [];
    public array $integrationsByUid = [];
    public array $integrationsByHandle = [];
    public ?array $captchas = null;
    public array $captchasByHandle = [];
    public array $enabledIntegrationsByForm = [];
    public array $captchaArgumentsByForm = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->groupedIntegrationTypes = [];
        $this->integrationsByType = [];
        $this->integrationsById = [];
        $this->integrationsByUid = [];
        $this->integrationsByHandle = [];
        $this->captchas = null;
        $this->captchasByHandle = [];
        $this->enabledIntegrationsByForm = [];
        $this->captchaArgumentsByForm = [];
    }
}
