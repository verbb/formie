<?php
namespace verbb\formie\models;

use craft\base\Model;

class MicrosoftDynamics365Entity extends Model
{
    // Properties
    // =========================================================================

    public ?string $handle = null;
    public ?string $label = null;
    public ?string $pluralLabel = null;
    public ?string $optionSourceLabel = null;
    public ?string $logicalName = null;
    public ?string $entitySetName = null;
    public ?string $primaryIdAttribute = null;
    public ?string $enabledSetting = null;
    public ?string $mappingSetting = null;
    public int $deliveryOrder = 1000;
    public bool $exposePicklists = true;


    // Public Methods
    // =========================================================================

    public function getPluralLabel(): string
    {
        return $this->pluralLabel ?: ($this->label . 's');
    }

    public function getOptionSourceLabel(): string
    {
        return $this->optionSourceLabel ?: $this->label;
    }

    public function getEnabledSetting(): string
    {
        return $this->enabledSetting ?: "customEntitySettings.{$this->handle}.enabled";
    }

    public function getMappingSetting(): string
    {
        return $this->mappingSetting ?: "customEntitySettings.{$this->handle}.fieldMapping";
    }

    public function usesCustomSettings(): bool
    {
        return $this->enabledSetting === null && $this->mappingSetting === null;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['handle', 'label', 'logicalName'], 'required'];
        $rules[] = [['handle', 'logicalName', 'entitySetName', 'primaryIdAttribute'], 'match', 'pattern' => '/^[a-zA-Z][a-zA-Z0-9_]*$/'];
        $rules[] = [['enabledSetting', 'mappingSetting'], 'match', 'pattern' => '/^[a-zA-Z][a-zA-Z0-9_.]*$/'];
        $rules[] = [['deliveryOrder'], 'integer'];
        $rules[] = [['exposePicklists'], 'boolean'];

        return $rules;
    }
}
