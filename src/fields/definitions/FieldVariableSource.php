<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\helpers\Variables;

use yii\base\BaseObject;

/**
 * Describes one value source that variable pickers and token-aware UIs can expose.
 */
class FieldVariableSource extends BaseObject
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
        return self::make($selector->handle, $selector->label, $selector->handle)
            ->when($selector->condition)
            ->forVariablePicker($selector->supportsVariablePicker)
            ->forClient($selector->supportsClient)
            ->withContent($content)
            ->withTypes($types)
            ->withMeta($selector->meta);
    }


    // Properties
    // =========================================================================

    public string $key = '';
    public ?string $label = null;
    public string $selector = '';
    public string $content = Variables::CONTENT_SINGLE_LINE;
    public array $types = [];
    public ?string $condition = null;
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

    public function withContent(string $content): self
    {
        $normalized = trim($content);
        $this->content = $normalized !== '' ? $normalized : Variables::CONTENT_SINGLE_LINE;

        return $this;
    }

    public function withTypes(array $types): self
    {
        $this->types = array_values(array_unique(array_filter(array_map('strval', $types))));

        return $this;
    }

    public function withMeta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
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
