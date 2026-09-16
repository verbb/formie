<?php
namespace verbb\formie\query;

use Craft;

use yii\db\Expression;

use InvalidArgumentException;

class NumericValueQueryHelper
{
    // Static Methods
    // =========================================================================

    public static function applyRangeConditions(array $condition, array &$params): array
    {
        static $parameterCount = 0;
        $operator = $condition[0] ?? null;

        if (in_array($operator, ['<', '<=', '>', '>='], true)) {
            if (!is_numeric($condition[2])) {
                throw new InvalidArgumentException('Invalid numeric comparison value.');
            }

            do {
                $parameter = ':formieNumeric' . ++$parameterCount;
            } while (array_key_exists($parameter, $params));

            $params[$parameter] = (string)$condition[2];
            $condition[1] = self::buildSortKeySql($condition[1]);
            $condition[2] = new Expression(self::buildSortKeySql($parameter, false));
        } elseif (is_string($operator) && in_array(strtolower($operator), ['and', 'or', 'not'], true)) {
            foreach ($condition as $key => $value) {
                if ($key !== 0 && is_array($value)) {
                    $condition[$key] = self::applyRangeConditions($value, $params);
                }
            }
        }

        return $condition;
    }

    /**
     * Numeric fields retain decimal strings. Order their sign, magnitude and
     * significant digits without rounding the coefficient through a SQL cast.
     */
    public static function buildSortKeySql(string $valueSql, bool $submissionValue = true): string
    {
        $db = Craft::$app->getDb();
        $pgsql = $db->getIsPgsql();
        $identity = $submissionValue ? 'numeric_source_id, ' : '';
        $match = $pgsql ? ' ~ ' : ' REGEXP ';
        $pattern = $db->quoteValue('^[+-]?([0-9]+([.][0-9]*)?|[.][0-9]+)([eE][+-]?[0-9]+)?$');
        $sourceId = $submissionValue ? '[[formie_submissions.id]] AS numeric_source_id, ' : '';
        $sourceTable = $submissionValue ? ' FROM {{%formie_submissions}} [[formie_submissions]]' : '';
        $source = "(SELECT {$sourceId}$valueSql AS numeric_value$sourceTable) number_input";
        $source = "(SELECT {$identity}CASE WHEN TRIM(numeric_value)$match$pattern THEN TRIM(numeric_value) ELSE NULL END AS raw FROM $source) number_raw";
        $unsigned = "REGEXP_REPLACE(raw, '^[+-]', ''" . ($pgsql ? ", 'g'" : '') . ')';
        $source = "(SELECT {$identity}raw, LOWER($unsigned) AS unsigned_value, CASE WHEN LEFT(raw, 1) = '-' THEN -1 ELSE 1 END AS direction FROM $source) number_sign";
        $coefficient = $pgsql ? "SPLIT_PART(unsigned_value, 'e', 1)" : "SUBSTRING_INDEX(unsigned_value, 'e', 1)";
        $exponent = $pgsql ? "SPLIT_PART(unsigned_value, 'e', 2)" : "SUBSTRING_INDEX(unsigned_value, 'e', -1)";
        $source = "(SELECT {$identity}raw, direction, $coefficient AS coefficient, CASE WHEN POSITION('e' IN unsigned_value) > 0 THEN $exponent ELSE '0' END AS explicit_exponent FROM $source) number_coefficient";
        $source = "(SELECT {$identity}raw, direction, explicit_exponent, REPLACE(coefficient, '.', '') AS all_digits, POSITION('.' IN CONCAT(coefficient, '.')) - 1 AS integer_length FROM $source) number_digits";
        $source = "(SELECT {$identity}raw, direction, CAST(explicit_exponent AS DECIMAL(65,0)) + integer_length - LENGTH(all_digits) + LENGTH(TRIM(LEADING '0' FROM all_digits)) - 1 AS magnitude, TRIM(TRAILING '0' FROM TRIM(LEADING '0' FROM all_digits)) AS digits FROM $source) number_magnitude";
        $source = "(SELECT {$identity}raw, digits, CASE WHEN digits = '' THEN 0 ELSE direction END AS sign, CASE WHEN direction < 0 THEN -magnitude ELSE magnitude END AS sort_magnitude FROM $source) number_normalized";
        $textType = $pgsql ? 'TEXT' : 'CHAR';
        $source = "(SELECT {$identity}raw, digits, sign, sort_magnitude, CAST(ABS(sort_magnitude) AS $textType) AS exponent_digits FROM $source) number_exponent";
        $negativeExponent = self::_complementDigits('exponent_digits', $pgsql);
        $negativeDigits = self::_complementDigits('digits', $pgsql);
        $exponentKey = "CASE WHEN sort_magnitude < 0 THEN CONCAT('0', LPAD(CAST(65 - LENGTH(exponent_digits) AS $textType), 2, '0'), $negativeExponent, ':') WHEN sort_magnitude = 0 THEN '1' ELSE CONCAT('2', LPAD(CAST(LENGTH(exponent_digits) AS $textType), 2, '0'), exponent_digits, '/') END";
        $key = "CASE WHEN raw IS NULL THEN NULL WHEN sign = 0 THEN '1' WHEN sign < 0 THEN CONCAT('0', $exponentKey, ':', $negativeDigits, ':') ELSE CONCAT('2', $exponentKey, ':', digits, '/') END";
        $query = "SELECT {$identity}$key AS numeric_key FROM $source";

        // Correlate by the primary key outside the derived tables for MariaDB.
        $where = $submissionValue ? ' WHERE number_key.numeric_source_id = [[formie_submissions.id]]' : '';
        $sql = "(SELECT numeric_key FROM ($query) number_key$where)";

        return $pgsql ? $sql . ' COLLATE "C"' : "CAST($sql AS BINARY)";
    }

    private static function _complementDigits(string $sql, bool $pgsql): string
    {
        if ($pgsql) {
            return "TRANSLATE($sql, '0123456789', '9876543210')";
        }

        foreach (str_split('0123456789') as $index => $digit) {
            $placeholder = chr(97 + $index);
            $sql = "REPLACE($sql, '$digit', '$placeholder')";
        }

        foreach (str_split('9876543210') as $index => $digit) {
            $placeholder = chr(97 + $index);
            $sql = "REPLACE($sql, '$placeholder', '$digit')";
        }

        return $sql;
    }
}
