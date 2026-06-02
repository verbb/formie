<?php
namespace verbb\formie\web;

final class FieldRenderCallContext
{
    // Properties
    // =========================================================================

    private static array $stack = [];
    

    // Static Methods
    // =========================================================================

    public static function push(array $context): void
    {
        self::$stack[] = $context;
    }

    public static function pop(): void
    {
        array_pop(self::$stack);
    }

    public static function current(): array
    {
        if (!self::$stack) {
            return [];
        }

        return self::$stack[count(self::$stack) - 1];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::current()[$key] ?? $default;
    }
}
