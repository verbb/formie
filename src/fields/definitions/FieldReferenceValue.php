<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\helpers\Variables;

use yii\base\BaseObject;

/**
 * Author-facing definition for one field reference value.
 * A single value can drive both selector metadata and variable-picker sources.
 */
class FieldReferenceValue extends BaseObject
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

    public string $handle = '';
    public ?string $label = null;
    public string $content = Variables::CONTENT_SINGLE_LINE;
    public array $variableTypes = [];
    public bool $default = false;
    public ?string $condition = null;
    public bool $supportsFieldSelect = true;
    public bool $supportsVariablePicker = true;
    public bool $supportsClient = true;
    public array $meta = [];


    // Public Methods
    // =========================================================================

    public function when(?string $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    public function withContent(string $content): self
    {
        $normalized = trim($content);
        $this->content = $normalized !== '' ? $normalized : Variables::CONTENT_SINGLE_LINE;

        return $this;
    }

    public function withVariableTypes(array $types): self
    {
        $this->variableTypes = array_values(array_unique(array_filter(array_map('strval', $types))));

        return $this;
    }

    public function forFieldSelect(bool $enabled = true): self
    {
        $this->supportsFieldSelect = $enabled;

        return $this;
    }

    public function forVariablePicker(bool $enabled = true): self
    {
        $this->supportsVariablePicker = $enabled;

        return $this;
    }

    public function forClient(bool $enabled = true): self
    {
        $this->supportsClient = $enabled;

        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function toReferenceSelectorDefinition(): ?FieldReferenceSelector
    {
        if ($this->handle === '') {
            return null;
        }

        return FieldReferenceSelector::make($this->handle, $this->label ?? $this->handle)
            ->when($this->condition)
            ->forFieldSelect($this->supportsFieldSelect)
            ->forVariablePicker($this->supportsVariablePicker)
            ->forClient($this->supportsClient)
            ->withMeta($this->meta);
    }

    public function toDefaultVariableSourceDefinition(): ?FieldVariableSource
    {
        if ($this->variableTypes === []) {
            return null;
        }

        return FieldVariableSource::make('value', $this->label ?? 'Value')
            ->when($this->condition)
            ->forVariablePicker($this->supportsVariablePicker)
            ->forClient($this->supportsClient)
            ->withContent($this->content)
            ->withTypes($this->variableTypes)
            ->withMeta($this->meta);
    }

    public function toSelectorVariableSourceDefinition(): ?FieldVariableSource
    {
        if ($this->handle === '' || $this->variableTypes === []) {
            return null;
        }

        return FieldVariableSource::make($this->handle, $this->label ?? $this->handle, $this->handle)
            ->when($this->condition)
            ->forVariablePicker($this->supportsVariablePicker)
            ->forClient($this->supportsClient)
            ->withContent($this->content)
            ->withTypes($this->variableTypes)
            ->withMeta($this->meta);
    }
}
