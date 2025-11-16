<?php
namespace verbb\formie\events;

use yii\base\Event;

class ModifyMigrationAddressConfigEvent extends Event
{
    // Properties
    // =========================================================================

    public array $settings = [];
    public array $addressConfig = [];
}
