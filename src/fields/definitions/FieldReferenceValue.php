<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\helpers\Variables;



/**
 * Author-facing definition for one field reference value.
 * A single value can drive both selector metadata and variable-picker sources.
 */
final class FieldReferenceValue
{
    // Static Methods
    // =========================================================================

    public static function make(array|string $handle = '', ?string $label = null): self
    {
        if (is_array($handle)) {
            return self::fromArray($handle);
        }

        return new self([
            'handle' => $handle,
            'label' => $label,
        ]);
    }

    public static function default(array $config = []): self
    {
        return self::fromArray([
            ...$config,
            'default' => true,
        ]);
    }

    public static function property(array|string $handle = '', ?string $label = null): self
    {
        return self::make($handle, $label);
    }

    public static function fromArray(array $config): self
    {
        return new self([
            'handle' => (string)($config['handle'] ?? ''),
            'label' => $config['label'] ?? null,
            'content' => (string)($config['content'] ?? Variables::CONTENT_SINGLE_LINE),
            'variableTypes' => (array)($config['variableTypes'] ?? []),
            'default' => (bool)($config['default'] ?? false),
            'condition' => $config['if'] ?? $config['condition'] ?? null,
            'supportsFieldSelect' => (bool)($config['supportsFieldSelect'] ?? true),
            'supportsVariablePicker' => (bool)($config['supportsVariablePicker'] ?? true),
            'supportsClient' => (bool)($config['supportsClient'] ?? $config['supportsRuntime'] ?? true),
            'meta' => (array)($config['meta'] ?? []),
        ]);
    }


    // Properties
    // =========================================================================

    public readonly string $handle;
    public readonly ?string $label;
    public readonly string $content;
    public readonly array $variableTypes;
    public readonly bool $default;
    public readonly ?string $condition;
    public readonly bool $supportsFieldSelect;
    public readonly bool $supportsVariablePicker;
    public readonly bool $supportsClient;
    public readonly array $meta;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $this->handle = $config['handle'] ?? '';
        $this->label = $config['label'] ?? null;
        $this->content = $config['content'] ?? Variables::CONTENT_SINGLE_LINE;
        $this->variableTypes = $config['variableTypes'] ?? [];
        $this->default = $config['default'] ?? false;
        $this->condition = $config['condition'] ?? null;
        $this->supportsFieldSelect = $config['supportsFieldSelect'] ?? true;
        $this->supportsVariablePicker = $config['supportsVariablePicker'] ?? true;
        $this->supportsClient = $config['supportsClient'] ?? true;
        $this->meta = $config['meta'] ?? [];
    }

    public function toReferenceSelectorDefinition(): ?FieldReferenceSelector
    {
        if ($this->handle === '') {
            return null;
        }

        return FieldReferenceSelector::fromArray([
            'handle' => $this->handle, 'label' => $this->label ?? $this->handle,
            'condition' => $this->condition, 'supportsFieldSelect' => $this->supportsFieldSelect,
            'supportsVariablePicker' => $this->supportsVariablePicker, 'supportsClient' => $this->supportsClient, 'meta' => $this->meta,
        ]);
    }

    public function toDefaultVariableSourceDefinition(): ?FieldVariableSource
    {
        if ($this->variableTypes === []) {
            return null;
        }

        return new FieldVariableSource([
            'key' => 'value', 'label' => $this->label ?? 'Value', 'selector' => '',
            'condition' => $this->condition, 'supportsVariablePicker' => $this->supportsVariablePicker,
            'supportsClient' => $this->supportsClient, 'content' => $this->content, 'types' => $this->variableTypes, 'meta' => $this->meta,
        ]);
    }

    public function toSelectorVariableSourceDefinition(): ?FieldVariableSource
    {
        if ($this->handle === '' || $this->variableTypes === []) {
            return null;
        }

        return new FieldVariableSource([
            'key' => $this->handle, 'label' => $this->label ?? 'Value', 'selector' => $this->handle,
            'condition' => $this->condition, 'supportsVariablePicker' => $this->supportsVariablePicker,
            'supportsClient' => $this->supportsClient, 'content' => $this->content, 'types' => $this->variableTypes, 'meta' => $this->meta,
        ]);
    }
}
