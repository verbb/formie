<?php
namespace verbb\formie\helpers;

use verbb\base\helpers\ArrayHelper as BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    // Static Methods
    // =========================================================================

    public static function recursiveImplode(string $glue = ',', array $array, bool $include_keys = false, bool $trim_all = false): string
    {
        $glued_string = '';

        // Recursively iterates array and adds key/value to glued string
        array_walk_recursive($array, function($value, $key) use ($glue, $include_keys, &$glued_string) {
            $include_keys && $glued_string .= $key . $glue;
            $glued_string .= $value . $glue;
        });

        // Removes last $glue from string
        $glue !== '' && $glued_string = substr($glued_string, 0, -strlen($glue));

        // Trim ALL whitespace
        $trim_all && $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);

        return (string)$glued_string;
    }

}
