<?php
namespace verbb\formie\base;

use craft\base\Component;
use craft\helpers\StringHelper;

abstract class PredefinedOption extends Component implements PredefinedOptionInterface
{
    // Protected Properties
    // =========================================================================

    public static $defaultLabelOption;
    public static $defaultValueOption;


    // Public Method
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $classNameParts = explode('\\', get_class($this));
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }

    /**
     * @inheritDoc
     */
    public static function getLabelOptions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getValueOptions(): array
    {
        return [];
    }
}
