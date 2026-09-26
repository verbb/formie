<?php
namespace verbb\formie\fields\definitions;

/**
 * Normalized selector metadata for token UIs and field-reference pickers.
 */
final class FieldReferenceSelector
{
    // Static Methods
    // =========================================================================

    public static function make(string $handle, string $label): self
    {
        return new self([
            'handle' => $handle,
            'label' => $label,
        ]);
    }

    public static function fromArray(array $config): self
    {
        return new self([
            'label' => (string)($config['label'] ?? ''),
            'handle' => (string)($config['handle'] ?? ''),
            'condition' => $config['if'] ?? $config['condition'] ?? null,
            'supportsFieldSelect' => (bool)($config['supportsFieldSelect'] ?? true),
            'supportsVariablePicker' => (bool)($config['supportsVariablePicker'] ?? true),
            'supportsClient' => (bool)($config['supportsClient'] ?? $config['supportsRuntime'] ?? true),
            'meta' => (array)($config['meta'] ?? []),
        ]);
    }
    

    // Properties
    // =========================================================================

    public readonly string $label;
    public readonly string $handle;
    public readonly ?string $condition;
    public readonly bool $supportsFieldSelect;
    public readonly bool $supportsVariablePicker;
    public readonly bool $supportsClient;
    public readonly array $meta;

    
    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $this->label = $config['label'] ?? '';
        $this->handle = $config['handle'] ?? '';
        $this->condition = $config['condition'] ?? null;
        $this->supportsFieldSelect = $config['supportsFieldSelect'] ?? true;
        $this->supportsVariablePicker = $config['supportsVariablePicker'] ?? true;
        $this->supportsClient = $config['supportsClient'] ?? true;
        $this->meta = $config['meta'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'handle' => $this->handle,
            'condition' => $this->condition,
            'supportsFieldSelect' => $this->supportsFieldSelect,
            'supportsVariablePicker' => $this->supportsVariablePicker,
            'supportsClient' => $this->supportsClient,
            'meta' => $this->meta,
        ];
    }
}
