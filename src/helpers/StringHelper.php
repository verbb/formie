<?php
namespace verbb\formie\helpers;

use craft\helpers\StringHelper as CraftStringHelper;

use voku\helper\AntiXSS;

class StringHelper extends CraftStringHelper
{
    public static function cleanString(string $str): string
    {
        return (new AntiXSS())->xss_clean($str);
    }
}