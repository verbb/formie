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

    public static function getRuler()
    {
        $ruler = new Ruler();

        $ruler->getDefaultAsserter()->setOperator('contains', function($subject, $pattern) {
            return StringHelper::contains($subject, $pattern);
        });

        $ruler->getDefaultAsserter()->setOperator('startswith', function($subject, $pattern) {
            return StringHelper::startsWith($subject, $pattern);
        });

        $ruler->getDefaultAsserter()->setOperator('endswith', function($subject, $pattern) {
            return StringHelper::endsWith($subject, $pattern);
        });

        return $ruler;
    }

    public static function getContext($conditions = [])
    {
        return new Context($conditions);
    }

}
