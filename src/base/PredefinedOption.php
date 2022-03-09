<?php
namespace verbb\formie\base;

use craft\base\Component;
use craft\helpers\StringHelper;

abstract class PredefinedOption extends Component implements PredefinedOptionInterface
{
    // Protected Properties
    // =========================================================================

    public static ?string $defaultLabelOption = null;
    public static ?string $defaultValueOption = null;


    // Public Method
    // =========================================================================

    public function __toString()
    {
        $classNameParts = explode('\\', get_class($this));
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }

    public static function getLabelOptions(): array
    {
        return [];
    }

    public static function getValueOptions(): array
    {
        return [];
    }
}
