<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;

use yii\base\Event;

class RegisterBrowserModulesEvent extends Event
{
    // Properties
    // =========================================================================

    public Form $form;
    public string $surface;
    public array $modules = [];
}
