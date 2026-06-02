<?php
namespace verbb\formie\helpers;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class CalculationsHelper
{
    // Static Methods
    // =========================================================================
    
    public static function getEvaluator(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();

        $expressionLanguage->register('contains', function() {
        }, function($args, $subject, $pattern) {
            if (is_array($subject)) {
                return in_array($pattern, $subject);
            }

            return StringHelper::contains((string)$subject, $pattern);
        });

        $expressionLanguage->register('notContains', function() {
        }, function($args, $subject, $pattern) {
            if (is_array($subject)) {
                return !in_array($pattern, $subject);
            }

            return !StringHelper::contains((string)$subject, $pattern);
        });

        $expressionLanguage->register('startsWith', function() {
        }, function($args, $subject, $pattern) {
            return str_starts_with((string)$subject, $pattern);
        });

        $expressionLanguage->register('endsWith', function() {
        }, function($args, $subject, $pattern) {
            return StringHelper::endsWith((string)$subject, $pattern);
        });

        $expressionLanguage->register('empty', function() {
        }, function($args, $subject) {
            if (is_null($subject)) {
                return true;
            }

            if (is_string($subject) && trim($subject) === '') {
                return true;
            }

            if (is_array($subject) && empty($subject)) {
                return true;
            }

            if (is_object($subject) && empty((array)$subject)) {
                return true;
            }

            return false;
        });

        $expressionLanguage->register('notEmpty', function() {
        }, function($args, $subject) {
            if (is_null($subject)) {
                return false;
            }

            if (is_string($subject) && trim($subject) === '') {
                return false;
            }

            if (is_array($subject) && empty($subject)) {
                return false;
            }

            if (is_object($subject) && empty((array)$subject)) {
                return false;
            }

            return true;
        });

        return $expressionLanguage;
    }
}
