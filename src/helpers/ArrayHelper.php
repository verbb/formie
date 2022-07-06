<?php
namespace verbb\formie\helpers;

use craft\helpers\ArrayHelper as CraftArrayHelper;

class ArrayHelper extends CraftArrayHelper
{
    // Static Methods
    // =========================================================================

    /**
     * Recursively implodes an array with optional key inclusion
     *
     * Example of $include_keys output: key, value, key, value, key, value
     *
     * @access  public
     * @param array $array multi-dimensional array to recursively implode
     * @param string $glue value that glues elements together
     * @param bool $include_keys include keys before their values
     * @param bool $trim_all trim ALL whitespace from string
     * @return  string  imploded array
     */
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

    /**
     * Collapses a multidimensional array into a single dimension, using a delimited array path for
     * each array element's key, i.e. [['Foo' => ['Bar' => 'Far']]] becomes
     * ['0.Foo.Bar' => 'Far']
     *
     * @param array $data Array to flatten
     * @param string $separator String used to separate array key elements in a path, defaults to '.'
     * @return array
     * @link https://book.cakephp.org/4/en/core-libraries/hash.html#Cake\Utility\Hash::flatten
     */
    public static function flatten(array $data, string $separator = '.'): array
    {
        $result = [];
        $stack = [];
        $path = '';

        reset($data);
        while (!empty($data)) {
            $key = key($data);
            $element = $data[$key];
            unset($data[$key]);

            if (is_array($element) && !empty($element)) {
                if (!empty($data)) {
                    $stack[] = [$data, $path];
                }
                $data = $element;
                reset($data);
                $path .= $key . $separator;
            } else {
                $result[$path . $key] = $element;
            }

            if (empty($data) && !empty($stack)) {
                [$data, $path] = array_pop($stack);
                reset($data);
            }
        }

        return $result;
    }

    /**
     * Expands a flat array to a nested array.
     *
     * For example, un-flattens an array that was collapsed with `Hash::flatten()`
     * into a multidimensional array. So, `['0.Foo.Bar' => 'Far']` becomes
     * `[['Foo' => ['Bar' => 'Far']]]`.
     *
     * @param array $data Flattened array
     * @param string $separator The delimiter used
     * @return array
     * @link https://book.cakephp.org/4/en/core-libraries/hash.html#Cake\Utility\Hash::expand
     */
    public static function expand(array $data, string $separator = '.'): array
    {
        $result = [];
        foreach ($data as $flat => $value) {
            $keys = explode($separator, (string)$flat);
            $keys = array_reverse($keys);

            $child = [
                $keys[0] => $value,
            ];

            array_shift($keys);

            foreach ($keys as $k) {
                $child = [
                    $k => $child,
                ];
            }

            $stack = [[$child, &$result]];
            static::_merge($stack, $result);
        }

        return $result;
    }

    /**
     * Merge helper function to reduce duplicated code between merge() and expand().
     *
     * @param array $stack The stack of operations to work with.
     * @param array $return The return value to operate on.
     * @return void
     */
    protected static function _merge(array $stack, array &$return): void
    {
        while (!empty($stack)) {
            foreach ($stack as $curKey => &$curMerge) {
                foreach ($curMerge[0] as $key => &$val) {
                    if (!is_array($curMerge[1])) {
                        continue;
                    }

                    if (
                        !empty($curMerge[1][$key])
                        && (array)$curMerge[1][$key] === $curMerge[1][$key]
                        && (array)$val === $val
                    ) {
                        // Recurse into the current merge data as it is an array.
                        $stack[] = [&$val, &$curMerge[1][$key]];
                    } else if ((int)$key === $key && isset($curMerge[1][$key])) {
                        $curMerge[1][] = $val;
                    } else {
                        $curMerge[1][$key] = $val;
                    }
                }
                unset($val, $stack[$curKey]);
            }
            unset($curMerge);
        }
    }

    /**
     * @inheritDoc
     */
    public static function filterNullValues($values)
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::filterNullValues($values[$key]);
            }

            if ($values[$key] === null) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public static function filterEmptyValues($values)
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::filterEmptyValues($values[$key]);
            }

            if ($values[$key] === null || $values[$key] === false || $values[$key] === '') {
                unset($values[$key]);
            }
        }

        return $values;
    }

}
