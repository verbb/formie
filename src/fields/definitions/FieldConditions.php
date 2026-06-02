<?php
namespace verbb\formie\fields\definitions;

use craft\helpers\Json;

use yii\base\BaseObject;

/**
 * Wrapper around normalized field-condition config so callers can ask for array
 * or JSON output without re-encoding the field's authored conditions each time.
 */
class FieldConditions extends BaseObject
{
    // Static Methods
    // =========================================================================

    public static function make(array $config = []): self
    {
        return new self(['config' => $config]);
    }
    

    // Properties
    // =========================================================================

    public array $config = [];

    
    // Public Methods
    // =========================================================================

    public function hasRules(): bool
    {
        return (bool)($this->config['conditions'] ?? $this->config['rules'] ?? []);
    }

    public function toArray(): array
    {
        return $this->config;
    }

    public function toJson(): ?string
    {
        if (!$this->hasRules()) {
            return null;
        }

        return Json::encode($this->config);
    }
}
