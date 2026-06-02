<?php
namespace verbb\formie\events;

use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;

use yii\base\Event;

class ModifyIntegrationFormSettingsSchemaEvent extends Event
{
    public array $schema = [];
    public Integration $integration;
    public FormInterface $form;
}
