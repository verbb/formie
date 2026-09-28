<?php
namespace verbb\formie\references;

use InvalidArgumentException;

final readonly class ReferenceCondition
{
    // Static Methods
    // =========================================================================

    public static function equals(string $property, mixed $value): self
    {
        return new self('equals', property: $property, value: $value);
    }

    public static function notEquals(string $property, mixed $value): self
    {
        return new self('notEquals', property: $property, value: $value);
    }

    public static function all(self ...$conditions): self
    {
        return new self('all', conditions: $conditions);
    }

    public static function any(self ...$conditions): self
    {
        return new self('any', conditions: $conditions);
    }


    // Public Methods
    // =========================================================================

    public function __construct(
        public string $operator,
        public string $property = '',
        public mixed $value = null,
        public array $conditions = [],
    ) {
        if (in_array($operator, ['equals', 'notEquals'], true)) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]*$/D', $property)) {
                throw new InvalidArgumentException('Reference condition properties must use a safe dotted path.');
            }

            return;
        }

        if (!in_array($operator, ['all', 'any'], true) || $conditions === []) {
            throw new InvalidArgumentException('Invalid reference condition.');
        }

        foreach ($conditions as $condition) {
            if (!$condition instanceof self) {
                throw new InvalidArgumentException('Reference condition groups may contain only reference conditions.');
            }
        }
    }

    public function matches(object|array $settings): bool
    {
        if ($this->operator === 'all') {
            foreach ($this->conditions as $condition) {
                if (!$condition->matches($settings)) {
                    return false;
                }
            }

            return true;
        }

        if ($this->operator === 'any') {
            foreach ($this->conditions as $condition) {
                if ($condition->matches($settings)) {
                    return true;
                }
            }

            return false;
        }

        $value = $settings;

        foreach (explode('.', $this->property) as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } elseif (is_object($value) && (isset($value->$part) || property_exists($value, $part))) {
                $value = $value->$part;
            } else {
                $value = null;
                break;
            }
        }

        return $this->operator === 'equals' ? $value === $this->value : $value !== $this->value;
    }

    public function toArray(): array
    {
        if (in_array($this->operator, ['all', 'any'], true)) {
            return [
                'operator' => $this->operator,
                'conditions' => array_map(static fn(self $condition): array => $condition->toArray(), $this->conditions),
            ];
        }

        return [
            'operator' => $this->operator,
            'property' => $this->property,
            'value' => $this->value,
        ];
    }
}
