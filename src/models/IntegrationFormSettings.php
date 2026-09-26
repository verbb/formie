<?php
namespace verbb\formie\models;

use verbb\formie\helpers\ArrayHelper;

use craft\base\Model;

class IntegrationFormSettings extends Model
{
    // Properties
    // =========================================================================

    public array $collections = [];
    public string $classKey = 'class';


    // Public Methods
    // =========================================================================

    public function __construct($collections = [])
    {
        parent::__construct();

        $this->collections = $collections;
    }

    public function getSettings(): array
    {
        return $this->collections;
    }

    public function getSettingsByKey(string $key)
    {
        return ArrayHelper::getValue($this->collections, $key) ?? [];
    }

    public function setSettings(array $collections): array
    {
        return array_merge($this->collections, $collections);
    }

    public function setSettingsByKey(string $key, mixed $value): void
    {
        ArrayHelper::setValue($this->collections, $key, $value);
    }

    public function serialize()
    {
        return $this->_classToArray($this->collections);
    }

    public function unserialize($serialized): void
    {
        $this->collections = $this->_classFromArray($serialized);
    }

    // Private Methods
    // =========================================================================

    private function _classToArray(mixed $data): mixed
    {
        return IntegrationConfig::encode($data);
    }

    private function _classFromArray(mixed $data): mixed
    {
        return IntegrationConfig::decode($data);
    }
}
