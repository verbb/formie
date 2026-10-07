<?php
namespace verbb\formie\base;

use verbb\formie\helpers\StringHelper;

use craft\base\Component;

abstract class PredefinedOption extends Component implements PredefinedOptionInterface
{
    // Static Methods
    // =========================================================================

    public static function getLabelOptions(): array
    {
        return [];
    }

    public static function getValueOptions(): array
    {
        return [];
    }

    public static function toFieldOptions(): array
    {
        $options = [];

        foreach (static::getDataOptions() as $row) {
            if (is_array($row)) {
                $label = trim((string)($row['label'] ?? $row['name'] ?? ''));
            } else {
                $label = trim((string)$row);
            }

            if ($label === '') {
                continue;
            }

            $options[] = [
                'label' => $label,
                'value' => '',
                'default' => false,
            ];
        }

        return $options;
    }


    // Properties
    // =========================================================================

    public static ?string $defaultLabelOption = null;
    public static ?string $defaultValueOption = null;


    // Public Methods
    // =========================================================================

    // Public Method
    // =========================================================================

    public function __toString()
    {
        $classNameParts = explode('\\', get_class($this));
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }
}
