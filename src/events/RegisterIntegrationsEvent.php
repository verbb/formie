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
    public ?array $payments = [];
    public ?array $webhooks = [];
    public ?array $miscellaneous = [];
    
}
