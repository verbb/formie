<?php
namespace verbb\formie\client;

use craft\base\Model;

class BaseClientModel extends Model
{
    // Public Methods
    // =========================================================================

    public function toArrayRecursive(): array
    {
        $data = [];

        foreach ($this->attributes() as $attribute) {
            $data[$attribute] = $this->_normalizeValue($this->$attribute);
        }

        return $data;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArrayRecursive();
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->_normalizeValue($item), $value);
        }

        return $value;
    }
}
