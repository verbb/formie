<?php
namespace verbb\formie\migrations\plugins;

class Line
{
    public static function info(string $message, int $depth = 0, array $context = []): MigrationLine
    {
        return new MigrationLine('info', $message, $depth, $context);
    }

    public static function success(string $message, int $depth = 0, array $context = []): MigrationLine
    {
        return new MigrationLine('success', $message, $depth, $context);
    }

    public static function warning(string $message, int $depth = 0, array $context = []): MigrationLine
    {
        return new MigrationLine('warning', $message, $depth, $context);
    }

    public static function error(string $message, int $depth = 0, array $context = []): MigrationLine
    {
        return new MigrationLine('error', $message, $depth, $context);
    }
}

