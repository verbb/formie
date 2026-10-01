<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\references\ReferenceCondition;
use verbb\formie\references\ReferenceShape;
use verbb\formie\references\ReferenceType;

use InvalidArgumentException;

/** Describes one public value exposed by a field reference. */
final readonly class FieldReferenceValue
{
    // Static Methods
    // =========================================================================

    public static function primary(
        ?string $label = null,
        array $types = [ReferenceType::Text],
        ReferenceShape $shape = ReferenceShape::Inline,
        ?ReferenceCondition $when = null,
        bool $supportsFieldSelect = true,
        bool $supportsVariablePicker = true,
        bool $supportsBrowser = true,
        bool $allowTransforms = true,
        array $meta = [],
    ): self {
        return new self(null, $label, $types, $shape, $when, $supportsFieldSelect, $supportsVariablePicker, $supportsBrowser, $allowTransforms, $meta);
    }

    public static function selector(
        string $key,
        string $label,
        array $types = [ReferenceType::Text],
        ReferenceShape $shape = ReferenceShape::Inline,
        ?ReferenceCondition $when = null,
        bool $supportsFieldSelect = true,
        bool $supportsVariablePicker = true,
        bool $supportsBrowser = true,
        bool $allowTransforms = true,
        array $meta = [],
    ): self {
        return new self($key, $label, $types, $shape, $when, $supportsFieldSelect, $supportsVariablePicker, $supportsBrowser, $allowTransforms, $meta);
    }


    // Public Methods
    // =========================================================================

    public function isPrimary(): bool
    {
        return $this->selector === null;
    }

    public function matchesSelector(string $selector): bool
    {
        if ($selector === '') {
            return $this->isPrimary();
        }

        return $this->selector === $selector || in_array($selector, $this->meta['aliases'] ?? [], true);
    }

    public function appliesTo(object|array $settings): bool
    {
        return $this->when?->matches($settings) ?? true;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->isPrimary() ? 'primary' : 'selector',
            'selector' => $this->selector,
            'label' => $this->label,
            'types' => array_map(static fn(ReferenceType $type): string => $type->value, $this->types),
            'shape' => $this->shape->value,
            'when' => $this->when?->toArray(),
            'supportsFieldSelect' => $this->supportsFieldSelect,
            'supportsVariablePicker' => $this->supportsVariablePicker,
            'supportsBrowser' => $this->supportsBrowser,
            'allowTransforms' => $this->allowTransforms,
            'meta' => $this->meta,
        ];
    }


    // Private Methods
    // =========================================================================

    private function __construct(
        public ?string $selector,
        public ?string $label,
        public array $types,
        public ReferenceShape $shape,
        public ?ReferenceCondition $when,
        public bool $supportsFieldSelect,
        public bool $supportsVariablePicker,
        public bool $supportsBrowser,
        public bool $allowTransforms,
        public array $meta,
    ) {
        if ($selector !== null && !preg_match('/^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$/D', $selector)) {
            throw new InvalidArgumentException('Reference selectors must use a safe identifier.');
        }

        if ($types === []) {
            throw new InvalidArgumentException('Reference values must declare at least one semantic type.');
        }

        foreach ($types as $type) {
            if (!$type instanceof ReferenceType) {
                throw new InvalidArgumentException('Reference values must use ReferenceType cases.');
            }
        }

        $aliases = $meta['aliases'] ?? [];

        if (!is_array($aliases)) {
            throw new InvalidArgumentException('Reference selector aliases must be an array.');
        }

        foreach ($aliases as $alias) {
            if (!is_string($alias) || !preg_match('/^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$/D', $alias)) {
                throw new InvalidArgumentException('Reference selector aliases must use safe identifiers.');
            }
        }
    }
}
