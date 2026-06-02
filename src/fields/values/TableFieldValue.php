<?php
namespace verbb\formie\fields\values;

use verbb\formie\helpers\ArrayHelper;

use DateTimeInterface;

class TableFieldValue implements FieldValueInterface
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

        if (is_array($value)) {
            return (new self($value))->toClientValue();
        }

        return $value;
    }


    // Properties
    // =========================================================================

    private array $_rows = [];
    private array $_columns = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $rows = [], array $columns = [])
    {
        $this->_rows = is_array($rows) ? array_values($rows) : [];
        $this->_columns = $columns;
    }

    public function isEmpty(): bool
    {
        return $this->_rows === [];
    }

    public function toValueArray(): array
    {
        return $this->_rows;
    }

    public function toClientValue(): mixed
    {
        return array_map(fn(mixed $row): mixed => $this->_serializeRow($row), $this->_rows);
    }

    public function toValueString(): string
    {
        return json_encode($this->toClientValue()) ?: '';
    }

    public function canResolvePath(string $path): bool
    {
        return $path !== '';
    }

    public function getPathValue(string $path): mixed
    {
        return $this->canResolvePath($path) ? ArrayHelper::getValue($this->toValueArray(), $path) : $this;
    }


    // Private Methods
    // =========================================================================

    private function _serializeRow(mixed $row): mixed
    {
        if (!is_array($row)) {
            return $row;
        }

        $serializedRow = [];

        foreach ($row as $key => $cellValue) {
            $serializedRow[$key] = $this->_serializeCellValue($key, $cellValue);
        }

        return $serializedRow;
    }

    private function _serializeCellValue(string|int $key, mixed $cellValue): mixed
    {
        $column = $this->_resolveColumn((string)$key);
        $columnType = $column['type'] ?? null;

        if ($columnType === 'date' && $cellValue instanceof DateTimeInterface) {
            return $cellValue->format('Y-m-d');
        }

        if ($columnType === 'time' && $cellValue instanceof DateTimeInterface) {
            return $cellValue->format('H:i');
        }

        if ($cellValue instanceof FieldValueInterface) {
            return $cellValue->toClientValue();
        }

        if (is_array($cellValue)) {
            return array_map(fn(mixed $item): mixed => $this->_serializeCellValue($key, $item), $cellValue);
        }

        return $cellValue;
    }

    private function _resolveColumn(string $key): ?array
    {
        if (isset($this->_columns[$key]) && is_array($this->_columns[$key])) {
            return $this->_columns[$key];
        }

        foreach ($this->_columns as $column) {
            if (($column['handle'] ?? null) === $key) {
                return $column;
            }
        }

        return null;
    }
}
