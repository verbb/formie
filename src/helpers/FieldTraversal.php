<?php
namespace verbb\formie\helpers;

use verbb\formie\base\ParentFieldInterface;

class FieldTraversal
{
    // Static Methods
    // =========================================================================

    public static function recursively(array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $result[] = $field;
            if ($field instanceof ParentFieldInterface) {
                array_push($result, ...self::recursively($field->getFields()));
            }
        }

        return $result;
    }
}
