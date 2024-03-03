<?php
namespace verbb\formie\base;

use craft\base\ComponentInterface;

interface PositionInterface extends ComponentInterface
{
    // Static Methods
    // =========================================================================

    public static function supports(FieldInterface $field = null): bool;
    public static function fallback(FieldInterface $field = null): ?string;


    // Public Methods
    // =========================================================================

    public function shouldDisplay(string $position): bool;
}
