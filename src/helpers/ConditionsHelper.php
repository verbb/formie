<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\StringHelper;

use Hoa\Ruler\Ruler;
use Hoa\Ruler\Context;

class ConditionsHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function getRuler()
    {
        $ruler = new Ruler();

        $ruler->getDefaultAsserter()->setOperator('contains', function($subject, $pattern) {
            if (is_array($subject)) {
                $subject = self::recursiveImplode(' ', $subject);
            }

            return StringHelper::contains($subject, $pattern);
        });

        $ruler->getDefaultAsserter()->setOperator('startswith', function($subject, $pattern) {
            if (is_array($subject)) {
                $subject = self::recursiveImplode(' ', $subject);
            }

            return StringHelper::startsWith($subject, $pattern);
        });

        $ruler->getDefaultAsserter()->setOperator('endswith', function($subject, $pattern) {
            if (is_array($subject)) {
                $subject = self::recursiveImplode(' ', $subject);
            }

            return StringHelper::endsWith($subject, $pattern);
        });

        return $ruler;
    }

    /**
     * @inheritDoc
     */
    public static function getContext($conditions = [])
    {
        return new Context($conditions);
    }

    /**
     * Recursively implodes an array with optional key inclusion
     * 
     * Example of $include_keys output: key, value, key, value, key, value
     * 
     * @access  public
     * @param   array   $array         multi-dimensional array to recursively implode
     * @param   string  $glue          value that glues elements together   
     * @param   bool    $include_keys  include keys before their values
     * @param   bool    $trim_all      trim ALL whitespace from string
     * @return  string  imploded array
     */ 
    public static function recursiveImplode($glue = ',', array $array, $include_keys = false, $trim_all = false)
    {
        $glued_string = '';

        // Recursively iterates array and adds key/value to glued string
        array_walk_recursive($array, function($value, $key) use ($glue, $include_keys, &$glued_string) {
            $include_keys && $glued_string .= $key.$glue;
            $glued_string .= $value.$glue;
        });

        // Removes last $glue from string
        strlen($glue) > 0 && $glued_string = substr($glued_string, 0, -strlen($glue));

        // Trim ALL whitespace
        $trim_all && $glued_string = preg_replace("/(\s)/ixsm", '', $glued_string);

        return (string)$glued_string;
    }
}
