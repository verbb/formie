<?php
namespace verbb\formie\base;

use craft\base\Component;
use craft\helpers\StringHelper;

abstract class Position extends Component implements PositionInterface
{
    // Protected Properties
    // =========================================================================

    /**
     * The position content should be rendered in the form, either:
     *
     * - above
     * - below
     */
    protected static $position;


    // Public Method
    // =========================================================================

    public function __toString()
    {
        $classNameParts = explode('\\', get_class($this));
        $end = array_pop($classNameParts);

        return StringHelper::toKebabCase($end);
    }

    /**
     * @inheritDoc
     */
    public function shouldDisplay(string $checkPosition): bool
    {
        return $checkPosition === $this::$position;
    }

    /**
     * @inheritDoc
     */
    public static function supports(FormFieldInterface $field = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function fallback(FormFieldInterface $field = null)
    {
        return null;
    }
}
