<?php
namespace verbb\formie\helpers;

use Craft;

class DbSchema
{
    // Static Methods
    // =========================================================================

    public static function tableExists(string $table): bool
    {
        static $cache = [];

        if (!array_key_exists($table, $cache)) {
            $cache[$table] = Craft::$app->getDb()->tableExists($table);
        }

        return $cache[$table];
    }

    public static function columnExists(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = self::tableExists($table)
                && Craft::$app->getDb()->columnExists($table, $column);
        }

        return $cache[$key];
    }
}
