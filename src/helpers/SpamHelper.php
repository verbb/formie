<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Settings;
use verbb\formie\parsers\SpamExpressionLanguage;

use Craft;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class SpamHelper
{
    // Static Methods
    // =========================================================================

    public static function checkContent(string $content): bool|array
    {
        $userIp = Craft::$app->getRequest()->userIP;
        $evaluator = self::_getEvaluator();

        /** @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Each line is one spam condition
        $lines = self::_getArrayFromMultiline($settings->spamKeywords);
        $twigLines = [];

        // Pre-parse the lines to swap out any Twig that should resolve to rules
        foreach ($lines as $key => $line) {
            if (str_contains($line, '{')) {
                unset($lines[$key]);
                $twigLines[] = self::_getArrayFromMultiline(Variables::getParsedValue($line));
            }
        }

        // For performance
        $lines = array_merge($lines, ...$twigLines);

        // We'll parse each line into an ExpressionLanguage string
        foreach ($lines as $line) {
            $expression = self::_parseLineToExpression($line);

            $result = $evaluator->evaluate($expression, [
                'content' => $content,
                'userIp' => $userIp,
            ]);

            // If any line is true => spam
            if ($result) {
                return [
                    'type' => self::_getRuleType($line),
                    'value' => $line,
                ];
            }
        }

        return false;
    }

    private static function _getEvaluator(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();

        $expressionLanguage->register('contains', function($haystack, $needle) {
        }, function ($args, $haystack, $needle) {
            // Use regex to match whole words, not `str_contains`, and ensure case-sensitive
            return preg_match('/\b' . preg_quote($needle, '/') . '\b/', $haystack) === 1;
        });

        $expressionLanguage->register('ipMatches', function($userIp, $ruleIp) {
        }, function(array $variables, string $userIp, string $rules) {
            // Split the rule string on commas for multiple definitions
            // e.g. "192.168.0.1, 192.168.0.2, 192.168.0.0/24"
            $targets = array_map('trim', explode(',', $rules));

            foreach ($targets as $target) {
                // Identify the type (single, range, or CIDR)
                if (strpos($target, '-') !== false) {
                    // Range
                    if (self::_ipInRange($userIp, $target)) {
                        return true;
                    }
                } else if (strpos($target, '/') !== false) {
                    // CIDR
                    if (self::_ipInCidr($userIp, $target)) {
                        return true;
                    }
                } else {
                    // Single IP
                    if (self::_ipIsEqual($userIp, $target)) {
                        return true;
                    }
                }
            }

            // If none matched, return false
            return false;
        });

        return $expressionLanguage;
    }

    private static function _parseLineToExpression(string $line): string
    {
        // Detect if it's a [match: ...] line
        if (preg_match('/^\[match:\s*(.*?)\]$/i', $line, $m)) {
            // $m[1] is "spam AND bulk" or "spam OR junk", etc.
            return self::_convertMatchSyntax($m[1]);
        }

        // Detect if it's [ip: ...]
        if (preg_match('/^\[ip:\s*(.*?)\]$/i', $line, $m)) {
            $ip = trim($m[1]);
            // Return expression "ipMatches(userIp, 'someIp')"
            return sprintf("ipMatches(userIp, '%s')", addslashes($ip));
        }

        // If none of the above, treat as plain text
        return sprintf("contains(content, '%s')", addslashes($line));
    }

    private static function _convertMatchSyntax(string $expr): string
    {
        // Split on ( ) or AND/OR
        $pattern = '/\s*(\(|\)|\b(?:AND|OR)\b)\s*/i';
        $tokens = preg_split($pattern, $expr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $parts = [];
        foreach ($tokens as $tok) {
            $tokTrim = trim($tok);

            // If it's an operator or parentheses, we pass it as-is, in lower case for ExpressionLanguage
            if (preg_match('/^(AND|OR|\(|\))$/i', $tokTrim)) {
                $parts[] = strtolower($tokTrim);
                continue;
            }

            // Otherwise, it's a keyword, wrap in "contains(content, 'keyword')"
            $escaped = addslashes($tokTrim);
            $parts[] = "contains(content, '{$escaped}')";
        }

        // Rejoin with spaces. ExpressionLanguage can parse: 
        // "( contains(...) or contains(...) ) and contains(...)"
        return implode(' ', $parts);
    }

    private static function _getRuleType(string $line): string
    {
        if (stripos($line, '[ip:') === 0) {
            return 'ip';
        }

        if (stripos($line, '[match:') === 0) {
            return 'text';
        }

        return 'text';
    }

    private static function _getArrayFromMultiline(string $string): array
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return array_filter($array);
    }

    private static function _ipIsEqual(string $userIp, string $targetIp): bool
    {
        return ($userIp === $targetIp);
    }

    private static function _ipInRange(string $userIp, string $range): bool
    {
        $userIpLong = ip2long($userIp);

        if ($userIpLong === false) {
            // If the user IP is invalid or not parsable, fail
            return false;
        }

        [$start, $end] = array_map('trim', explode('-', $range, 2));
        $startLong = ip2long($start);
        $endLong = ip2long($end);

        if ($startLong === false || $endLong === false) {
            return false;
        }

        // Ensure start <= end
        if ($startLong > $endLong) {
            [$startLong, $endLong] = [$endLong, $startLong];
        }

        return ($userIpLong >= $startLong && $userIpLong <= $endLong);
    }

    private static function _ipInCidr(string $userIp, string $cidr): bool
    {
        $userIpLong = ip2long($userIp);

        if ($userIpLong === false) {
            // If the user IP is invalid or not parsable, fail
            return false;
        }

        [$network, $maskBits] = explode('/', $cidr, 2);
        $mask = ~((1 << (32 - (int)$maskBits)) - 1);

        $networkLong = ip2long($network);

        if ($networkLong === false) {
            return false;
        }

        $net = $networkLong & $mask;
        $user = $userIpLong & $mask;

        return ($user === $net);
    }
}
