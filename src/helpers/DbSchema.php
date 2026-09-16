<?php
namespace verbb\formie\helpers;

use Craft;

class DbSchema
{
    // Static Methods
    // =========================================================================

    public static function tableExists(string $table): bool
    {
        // Craft invalidates its schema cache when migrations create or alter tables.
        // A separate negative cache would hide those changes for the entire process.
        return Craft::$app->getDb()->tableExists($table);
    }

    public static function columnExists(string $table, string $column): bool
    {
        return self::tableExists($table)
            && Craft::$app->getDb()->columnExists($table, $column);
    }
}
