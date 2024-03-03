<?php
namespace verbb\formie\base;

use verbb\formie\helpers\StringHelper;

use craft\base\Component;

abstract class Position extends Component implements PositionInterface
{
    // Properties
    // =========================================================================

    protected static ?string $position = null;


    // Static Method
    // =========================================================================

    public static function supports(FieldInterface $field = null): bool
    {
        return true;
    }

    public static function fallback(FieldInterface $field = null): ?string
    {
        return null;
    }


    // Public Method
    // =========================================================================

    public function __toString()
    {
        $classNameParts = explode('\\', get_class($this));
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }

    public function shouldDisplay(string $position): bool
    {
        return $position === $this::$position;
    }
}
