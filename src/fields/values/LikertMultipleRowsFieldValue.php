<?php
namespace verbb\formie\fields\values;

class LikertMultipleRowsFieldValue implements FieldValueInterface
{
    // Static Methods
    // =========================================================================

    public static function capabilityTypes(): array
    {
        return ['array'];
    }

    public static function toClientValueFrom(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toClientValue();
        }

        if (!is_array($value)) {
            return [];
        }

        return (new self($value))->toClientValue();
    }


    // Properties
    // =========================================================================

    /** @var array<string, SingleOptionFieldValue> */
    private array $_selections = [];

    /** @var array<string, string> */
    private array $_rowLabels = [];


    // Public Methods
    // =========================================================================

    /**
     * @param array<string, SingleOptionFieldValue> $selections
     * @param array<string, string> $rowLabels
     */
    public function __construct(array $selections = [], array $rowLabels = [])
    {
        $this->_rowLabels = $rowLabels;

        foreach ($selections as $rowKey => $selection) {
            if ($selection instanceof SingleOptionFieldValue) {
                $this->_selections[(string)$rowKey] = $selection;
            }
        }
    }

    public function setSelection(string $rowKey, SingleOptionFieldValue $selection): void
    {
        $this->_selections[$rowKey] = $selection;
    }

    public function getSelection(string $rowKey): ?SingleOptionFieldValue
    {
        return $this->_selections[$rowKey] ?? null;
    }

    /**
     * @return array<string, SingleOptionFieldValue>
     */
    public function selections(): array
    {
        return $this->_selections;
    }

    /**
     * @return array<string, string>
     */
    public function rowLabels(): array
    {
        return $this->_rowLabels;
    }

    public function isEmpty(): bool
    {
        if ($this->_selections === []) {
            return true;
        }

        foreach ($this->_selections as $selection) {
            if (!$selection->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $requiredRowKeys
     */
    public function isComplete(array $requiredRowKeys): bool
    {
        foreach ($requiredRowKeys as $rowKey) {
            $selection = $this->_selections[$rowKey] ?? null;

            if (!$selection || $selection->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    public function toClientValue(): array
    {
        $result = [];

        foreach ($this->_selections as $rowKey => $selection) {
            $result[$rowKey] = $selection->value;
        }

        return $result;
    }

    public function toValueArray(): array
    {
        $result = [];

        foreach ($this->_selections as $rowKey => $selection) {
            $result[$rowKey] = $selection->toValueArray();
        }

        return $result;
    }

    public function toValueString(): string
    {
        $parts = [];

        foreach ($this->_selections as $rowKey => $selection) {
            if ($selection->isEmpty()) {
                continue;
            }

            $rowLabel = $this->_rowLabels[$rowKey] ?? $rowKey;
            $parts[] = "{$rowLabel}: {$selection->getDisplayLabel()}";
        }

        return implode(', ', $parts);
    }

    public function __toString(): string
    {
        return $this->toValueString();
    }

    public function canResolvePath(string $path): bool
    {
        return $path !== '';
    }

    public function getPathValue(string $path): mixed
    {
        return $this->_selections[$path] ?? null;
    }
}
