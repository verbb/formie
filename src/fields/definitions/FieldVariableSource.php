<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\helpers\Variables;



/**
 * Describes one value source that variable pickers and token-aware UIs can expose.
 */
final class FieldVariableSource
{
    // Static Methods
    // =========================================================================

    public static function make(string $key, ?string $label = null, string $selector = ''): self
    {
        return new self([
            'key' => $key,
            'label' => $label,
            'selector' => $selector,
        ]);
    }

    public static function fromReferenceSelector(FieldReferenceSelector $selector, string $content = Variables::CONTENT_SINGLE_LINE, array $types = []): self
    {
        return new self([
            'key' => $selector->handle, 'label' => $selector->label, 'selector' => $selector->handle,
            'condition' => $selector->condition, 'supportsVariablePicker' => $selector->supportsVariablePicker,
            'supportsClient' => $selector->supportsClient, 'content' => $content, 'types' => $types, 'meta' => $selector->meta,
        ]);
    }


    // Properties
    // =========================================================================

    public readonly string $key;
    public readonly ?string $label;
    public readonly string $selector;
    public readonly string $content;
    public readonly array $types;
    public readonly ?string $condition;
    public readonly bool $supportsVariablePicker;
    public readonly bool $supportsClient;
    public readonly array $meta;
    

    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $this->key = $config['key'] ?? '';
        $this->label = $config['label'] ?? null;
        $this->selector = $config['selector'] ?? '';
        $this->content = $config['content'] ?? Variables::CONTENT_SINGLE_LINE;
        $this->types = $config['types'] ?? [];
        $this->condition = $config['condition'] ?? null;
        $this->supportsVariablePicker = $config['supportsVariablePicker'] ?? true;
        $this->supportsClient = $config['supportsClient'] ?? true;
        $this->meta = $config['meta'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'selector' => $this->selector,
            'content' => $this->content ?: Variables::CONTENT_SINGLE_LINE,
            'types' => $this->types,
            'condition' => $this->condition,
            'supportsVariablePicker' => $this->supportsVariablePicker,
            'supportsClient' => $this->supportsClient,
            'meta' => $this->meta,
        ];
    }
}
