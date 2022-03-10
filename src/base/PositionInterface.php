<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;

interface PositionInterface extends ComponentInterface
{
    // Static Methods
    // =========================================================================

    /**
     * Returns whether the provided field supports the position.
     *
     * @param FormFieldInterface|null $field
     * @return bool
     */
    public static function supports(FormFieldInterface $field = null): bool;

    /**
     * Returns the fallback position if the position is not supported.
     *
     * @param FormFieldInterface|null $field
     * @return string|null the PositionInterface class name
     */
    public static function fallback(FormFieldInterface $field = null): ?string;


    // Public Methods
    // =========================================================================

    /**
     * Returns whether content should be rendered at the provided position.
     *
     * @param string $position
     * @return bool
     */
    public function shouldDisplay(string $position): bool;
}
