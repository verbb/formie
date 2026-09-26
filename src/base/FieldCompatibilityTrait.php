<?php
namespace verbb\formie\base;

trait FieldCompatibilityTrait
{
    // Static Methods
    // =========================================================================

    private static function _hasLegacyStaticMethodOverride(string $method): bool
    {
        $reflection = new \ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }


    // Private Methods
    // =========================================================================

    private function _hasLegacyFieldMethodOverride(string $method): bool
    {
        $reflection = new \ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }
}
