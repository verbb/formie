<?php
namespace verbb\formie\query;

use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\db\QueryParam;

class NestedFieldQueryHelper
{
    // Static Methods
    // =========================================================================

    public static function buildQueryCondition(array $instances, mixed $value): ?array
    {
        $param = QueryParam::parse($value);

        if (empty($param->values)) {
            return null;
        }

        if ($param->operator === QueryParam::NOT) {
            $param->operator = QueryParam::OR;
            $negate = true;
        } else {
            $negate = false;
        }

        $valueSql = FieldValueQueryHelper::resolveCoalescedValueSql($instances);

        if ($valueSql === null) {
            return null;
        }

        $firstInstance = $instances[0] ?? null;

        if (!$firstInstance || !method_exists($firstInstance, 'getNestedFieldHandleUidMap')) {
            return null;
        }

        // We need to swap handles for UIDs to fetch content.
        // e.g. `['multiNameInGroup' => ['firstName' => 'Peter']]` to `['xxxxxxxx' => ['xxxxxxxx' => 'Peter']]`.
        // This is because JSON-searching can only be done once level at a time.
        $uidMap = $firstInstance->getNestedFieldHandleUidMap();
        $preppedValues = [];

        foreach (ArrayHelper::flatten($param->values) as $handlePath => $pathValue) {
            if (array_key_exists($handlePath, $uidMap)) {
                $preppedValues[$uidMap[$handlePath]] = $pathValue;
            }
        }

        if (empty($preppedValues)) {
            return null;
        }

        $db = Craft::$app->getDb();
        $criteria = ArrayHelper::expand($preppedValues);

        // Repeaters store an array of rows. MySQL's `JSON_CONTAINS` matches an object against any
        // array element, but PostgreSQL's `@>` needs an array on both sides to get the same result.
        if ($db->getIsPgsql() && $firstInstance instanceof RepeatableParentFieldInterface) {
            $criteria = [$criteria];
        }

        $condition = [$param->operator, $db->getQueryBuilder()->jsonContains($valueSql, $criteria)];

        return $negate ? ['not', $condition] : $condition;
    }
}
