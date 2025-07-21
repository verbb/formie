<?php
namespace verbb\formie\events;

use yii\base\Event;

class RegisterIntegrationsEvent extends Event
{
    // Properties
    // =========================================================================

    public ?array $addressProviders = [];
    public ?array $captchas = [];
    public ?array $elements = [];
    public ?array $emailMarketing = [];
    public ?array $crm = [];
    public ?array $helpDesk = [];
    public ?array $messaging = [];
    public ?array $payments = [];
    public ?array $automations = [];
    public ?array $miscellaneous = [];
    
    // Backward-compatibility until Formie 4
    public ?array $webhooks = [];    
}
