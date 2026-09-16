<?php
namespace verbb\formie\tiptap;

use InvalidArgumentException;

final class TextStyleDefinition
{
    // Constants
    // =========================================================================

    private const SAFE_VALUES = [
        'font-variant-caps' => ['small-caps', 'all-small-caps', 'petite-caps', 'all-petite-caps', 'unicase', 'titling-caps'],
        'text-transform' => ['uppercase', 'lowercase', 'capitalize'],
    ];


    // Public Methods
    // =========================================================================

    public function __construct(
        private readonly string $id,
        private readonly string $label,
        private readonly string $attribute,
        private readonly string $cssProperty,
        private readonly array $allowedValues,
        private readonly string $toolbarValue,
    ) {
        $this->_validate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getCssProperty(): string
    {
        return $this->cssProperty;
    }

    public function getAllowedValues(): array
    {
        return $this->allowedValues;
    }

    public function getToolbarValue(): string
    {
        return $this->toolbarValue;
    }


    // Private Methods
    // =========================================================================

    private function _validate(): void
    {
        if ($this->id === '' || trim($this->id) !== $this->id) {
            throw new InvalidArgumentException('A Formie TextStyle definition ID must be a non-empty, trimmed identifier.');
        }

        if ($this->label === '' || trim($this->label) !== $this->label) {
            throw new InvalidArgumentException("Formie TextStyle definition \"{$this->id}\" must have a non-empty, trimmed label.");
        }

        if (!preg_match('/^[a-z][A-Za-z0-9]*$/', $this->attribute)) {
            throw new InvalidArgumentException("Formie TextStyle definition \"{$this->id}\" must use a camel-cased attribute name.");
        }

        if ($this->attribute === 'fontVariantCaps') {
            throw new InvalidArgumentException('The core TextStyle attribute "fontVariantCaps" cannot be replaced.');
        }

        $safeValues = self::SAFE_VALUES[$this->cssProperty] ?? null;
        if ($safeValues === null) {
            throw new InvalidArgumentException("Formie TextStyle definition \"{$this->id}\" uses an unsupported CSS property.");
        }

        if (
            $this->allowedValues === [] ||
            array_filter($this->allowedValues, 'is_string') !== $this->allowedValues ||
            count(array_unique($this->allowedValues)) !== count($this->allowedValues)
        ) {
            throw new InvalidArgumentException("Formie TextStyle definition \"{$this->id}\" must provide unique allowed values.");
        }

        if (array_diff($this->allowedValues, $safeValues) !== []) {
            throw new InvalidArgumentException("Formie TextStyle definition \"{$this->id}\" contains an unsafe CSS value.");
        }

        if (!in_array($this->toolbarValue, $this->allowedValues, true)) {
            throw new InvalidArgumentException("Formie TextStyle definition \"{$this->id}\" toolbar value must be allowed.");
        }
    }
}
