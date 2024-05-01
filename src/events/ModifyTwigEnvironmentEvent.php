<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyTwigEnvironmentEvent extends Event
{
    // Properties
    // =========================================================================

    public array $allowedTags = [];
    public array $allowedFilters = [];
    public array $allowedFunctions = [];
    public array $allowedMethods = [];
    public array $allowedProperties = [];    
}
