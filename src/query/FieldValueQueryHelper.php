<?php
namespace verbb\formie\query;

use verbb\formie\base\Field;

use Craft;
use craft\helpers\Db;

use yii\db\ExpressionInterface;
use yii\db\Schema;
use InvalidArgumentException;

class FieldValueQueryHelper
{
    // Static Methods
    // =========================================================================

    public static function buildQueryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        $valueSql = self::resolveCoalescedValueSql($instances);

        if ($valueSql === null) {
            return false;
        }

        $columnType = self::resolveCoalescedColumnType($instances) ?? Schema::TYPE_JSON;
        $caseInsensitive = false;

        if (is_array($value) && isset($value['value'])) {
            $caseInsensitive = $value['caseInsensitive'] ?? false;
            $value = $value['value'];
        }

        return Db::parseParam($valueSql, $value, caseInsensitive: $caseInsensitive, columnType: $columnType);
    }

    public static function resolveCoalescedValueSql(array $instances, ?string $key = null): ?string
    {
        $valuesSql = array_filter(
            array_map(fn(Field $field) => $field->getValueSql($key), $instances),
            fn(?string $valueSql) => $valueSql !== null,
        );

        if (empty($valuesSql)) {
            return null;
        }

        if (count($valuesSql) === 1) {
            return reset($valuesSql);
        }

        return sprintf('COALESCE(%s)', implode(',', $valuesSql));
    }

    public static function resolveCoalescedColumnType(array $instances, ?string $key = null): ?string
    {
        $columnTypes = array_values(array_unique(array_filter(
            array_map(fn(Field $field) => $field->getValueColumnType($key), $instances),
            fn(?string $columnType) => $columnType !== null,
        )));

        if (empty($columnTypes)) {
            return null;
        }

        if (count($columnTypes) === 1) {
            return $columnTypes[0];
        }

        return Schema::TYPE_JSON;
    }

    public static function buildValueSql(string $fieldClass, ?string $fieldUid, array|string|null $dbType, ?string $key = null, string $contentColumn = 'formie_submissions.content'): ?string
    {
        if (!$fieldUid || $dbType === null) {
            return null;
        }

        [$resolvedDbType, $resolvedKey] = self::_resolveDbTypeForKey($fieldClass, $dbType, $key);

        $jsonPath = [$fieldUid];

        if ($resolvedKey !== null) {
            $jsonPath[] = $resolvedKey;
        }

        $db = Craft::$app->getDb();
        $qb = $db->getQueryBuilder();
        $sql = $qb->jsonExtract($contentColumn, $jsonPath);
        $castType = null;

        if ($db->getIsMysql()) {
            // If the field uses an optimized DB type, cast it so its values can be indexed.
            $castType = match (Db::parseColumnType($resolvedDbType)) {
                Schema::TYPE_CHAR,
                Schema::TYPE_STRING,
                'varchar' => 'CHAR(255)',
                // Only reliable way to compare booleans is as 'true'/'false' strings.
                Schema::TYPE_BOOLEAN => 'CHAR(5)',
                Schema::TYPE_DATE => 'DATE',
                Schema::TYPE_DATETIME => 'DATETIME',
                Schema::TYPE_DECIMAL => 'DECIMAL',
                Schema::TYPE_DOUBLE => 'DOUBLE',
                Schema::TYPE_FLOAT => 'FLOAT',
                Schema::TYPE_TINYINT,
                Schema::TYPE_SMALLINT,
                Schema::TYPE_INTEGER,
                Schema::TYPE_BIGINT => 'SIGNED',
                Schema::TYPE_TIME => 'TIME',
                default => null,
            };
        }

        // For PgSQL, decimals and integers can sort/compare incorrectly unless cast.
        if ($db->getIsPgsql()) {
            $castType = match (Db::parseColumnType($resolvedDbType)) {
                Schema::TYPE_DECIMAL => 'DECIMAL',
                Schema::TYPE_INTEGER => 'INTEGER',
                default => $castType,
            };
        }

        if ($castType !== null) {
            // If a length was specified, replace the default with that.
            $length = Db::parseColumnLength($resolvedDbType);

            if ($length) {
                $castType = preg_replace('/\(\d+\)/', "($length)", $castType);
            } else if ($castType === 'DECIMAL') {
                [$precision, $scale] = Db::parseColumnPrecisionAndScale($resolvedDbType) ?? [null, null];

                if ($precision && $scale) {
                    $castType .= "($precision,$scale)";
                }
            }

            $sql = "CAST($sql AS $castType)";
        }

        return $sql;
    }

    public static function resolveValueColumnType(string $fieldClass, array|string|null $dbType, ?string $key = null): ?string
    {
        if ($dbType === null) {
            return null;
        }

        [$resolvedDbType] = self::_resolveDbTypeForKey($fieldClass, $dbType, $key);

        return Db::parseColumnType($resolvedDbType);
    }

    // Private Methods
    // =========================================================================

    private static function _resolveDbTypeForKey(string $fieldClass, array|string $dbType, ?string $key = null): array
    {
        if ($key !== null && (!is_array($dbType) || !isset($dbType[$key]))) {
            throw new InvalidArgumentException(sprintf('%s doesn’t store values under the key “%s”.', $fieldClass, $key));
        }

        if (is_array($dbType)) {
            $key ??= array_key_first($dbType);

            return [$dbType[$key], $key];
        }

        return [$dbType, null];
    }
}
