<?php
namespace verbb\formie\base;

use verbb\formie\helpers\StringHelper;

use craft\base\Component;

abstract class PredefinedOption extends Component implements PredefinedOptionInterface
{
    // Properties
    // =========================================================================

    public static ?string $defaultLabelOption = null;
    public static ?string $defaultValueOption = null;


    // Static Method
    // =========================================================================

    public static function getLabelOptions(): array
    {
        return [];
    }

    public static function getValueOptions(): array
    {
        return [];
    }


    // Public Method
    // =========================================================================

    public function __toString()
    {
        $classNameParts = explode('\\', get_class($this));
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }
}
