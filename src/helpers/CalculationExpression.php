<?php
namespace verbb\formie\helpers;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\ConditionalNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\FunctionNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\Node\NullCoalesceNode;
use Symfony\Component\ExpressionLanguage\Node\UnaryNode;

use RuntimeException;
use stdClass;

/**
 * Evaluates a Calculations formula with JavaScript's rules, as the browser does through the `expression-language`
 * package. Symfony's parser builds the same syntax tree, but its evaluator follows PHP, where `50 + ''` throws and
 * `3 || 1` is `true`, so the tree is walked here instead.
 *
 * Numbers are floats, JavaScript arrays are PHP lists and object literals are `stdClass`.
 */
final class CalculationExpression
{
    // Static Methods
    // =========================================================================

    public static function evaluate(string $expression, array $variables): mixed
    {
        $variables = array_map(self::_fromPhp(...), $variables);
        $nodes = self::_language()->parse($expression, array_keys($variables))->getNodes();

        return self::_evaluateNode($nodes, $variables);
    }

    /** JavaScript's `Number()`. */
    public static function toNumber(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value) || is_bool($value)) {
            return (float)$value;
        }

        if ($value === null) {
            return 0.0;
        }

        if (is_array($value)) {
            return self::toNumber(self::toJsString($value));
        }

        if (!is_string($value)) {
            return NAN;
        }

        $value = self::_trim($value);

        if ($value === '') {
            return 0.0;
        }

        if (preg_match('/^([+-]?)Infinity$/D', $value, $match)) {
            return $match[1] === '-' ? -INF : INF;
        }

        if (preg_match('/^0([xob])([0-9a-f]+)$/Di', $value, $match)) {
            $base = ['x' => 16, 'o' => 8, 'b' => 2][strtolower($match[1])];

            return preg_match($base === 16 ? '/^[0-9a-f]+$/Di' : ($base === 8 ? '/^[0-7]+$/D' : '/^[01]+$/D'), $match[2])
                ? (float)base_convert($match[2], $base, 10)
                : NAN;
        }

        return preg_match('/^[+-]?(\d+\.?\d*|\.\d+)(e[+-]?\d+)?$/Di', $value) ? (float)$value : NAN;
    }

    /** JavaScript's `String()`. */
    public static function toJsString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if ($value instanceof stdClass) {
            return '[object Object]';
        }

        if (is_array($value)) {
            return implode(',', array_map(static fn($item) => $item === null ? '' : self::toJsString($item), $value));
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (!is_float($value)) {
            return (string)$value;
        }

        if (is_nan($value)) {
            return 'NaN';
        }

        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }

        if ($value == 0) {
            return '0';
        }

        // The shortest digits that read back as the same number, as JavaScript prints them.
        for ($precision = 1; $precision <= 17; $precision++) {
            $exponential = sprintf('%.' . ($precision - 1) . 'e', $value);

            if ((float)$exponential === $value) {
                break;
            }
        }

        [$mantissa, $exponent] = explode('e', $exponential);
        $sign = str_starts_with($mantissa, '-') ? '-' : '';
        $digits = rtrim(str_replace(['-', '.'], '', $mantissa), '0') ?: '0';
        $point = (int)$exponent + 1;

        if ($point > 21 || $point <= -6) {
            $fraction = strlen($digits) > 1 ? '.' . substr($digits, 1) : '';
            $power = $point - 1;

            return $sign . $digits[0] . $fraction . 'e' . ($power < 0 ? '-' : '+') . abs($power);
        }

        if ($point <= 0) {
            return $sign . '0.' . str_repeat('0', -$point) . $digits;
        }

        if ($point >= strlen($digits)) {
            return $sign . $digits . str_repeat('0', $point - strlen($digits));
        }

        return $sign . substr($digits, 0, $point) . '.' . substr($digits, $point);
    }

    /** JavaScript's `Number.prototype.toFixed()`, which rounds the exact value half away from zero. */
    public static function toFixed(float $value, int $decimals): string
    {
        if ($decimals < 0 || $decimals > 100) {
            throw new RuntimeException('toFixed() digits argument must be between 0 and 100.');
        }

        if (is_nan($value) || is_infinite($value) || abs($value) >= 1e21) {
            return self::toJsString($value);
        }

        $sign = $value < 0 ? '-' : '';
        [$whole, $fraction] = explode('.', sprintf('%.' . max(40, $decimals + 1) . 'f', abs($value)));
        $kept = $whole . substr($fraction, 0, $decimals);

        if ((int)$fraction[$decimals] >= 5) {
            $kept = self::_incrementDigits($kept);
        }

        $whole = substr($kept, 0, strlen($kept) - $decimals) ?: '0';

        return $sign . ($decimals > 0 ? $whole . '.' . substr($kept, -$decimals) : $whole);
    }

    /** JavaScript's truthiness, where an empty array is still true. */
    public static function isTruthy(mixed $value): bool
    {
        if (is_float($value)) {
            return $value != 0 && !is_nan($value);
        }

        if (is_array($value) || $value instanceof stdClass) {
            return true;
        }

        return $value !== null && $value !== false && $value !== '' && $value !== 0;
    }


    // Private Methods
    // =========================================================================

    private static function _language(): ExpressionLanguage
    {
        static $language = null;

        if ($language === null) {
            $language = new ExpressionLanguage();

            // Older Symfony releases don't parse these JavaScript built-ins. They're evaluated below.
            foreach (['min', 'max', 'constant', 'enum'] as $name) {
                $language->register($name, static fn() => '', static fn() => null);
            }
        }

        return $language;
    }

    private static function _evaluateNode(Node $node, array $variables): mixed
    {
        if ($node instanceof ConstantNode) {
            return self::_fromPhp($node->attributes['value']);
        }

        // Covers `NullCoalescedNameNode` too, which only reads a name that may not exist.
        if ($node instanceof NameNode || (isset($node->attributes['name']) && !$node instanceof FunctionNode)) {
            return $variables[$node->attributes['name']] ?? null;
        }

        if ($node instanceof UnaryNode) {
            $value = self::_evaluateNode($node->nodes['node'], $variables);

            return match ($node->attributes['operator']) {
                'not', '!' => !self::isTruthy($value),
                '-' => -self::toNumber($value),
                '~' => (float)~self::_toInt32($value),
                default => $value,
            };
        }

        if ($node instanceof BinaryNode) {
            return self::_evaluateBinary($node, $variables);
        }

        if ($node instanceof ConditionalNode) {
            return self::isTruthy(self::_evaluateNode($node->nodes['expr1'], $variables))
                ? self::_evaluateNode($node->nodes['expr2'], $variables)
                : self::_evaluateNode($node->nodes['expr3'], $variables);
        }

        if ($node instanceof NullCoalesceNode) {
            return self::_evaluateNode($node->nodes['expr1'], $variables) ?? self::_evaluateNode($node->nodes['expr2'], $variables);
        }

        if ($node instanceof ArrayNode) {
            return self::_evaluateArray($node, $variables);
        }

        if ($node instanceof FunctionNode) {
            $arguments = array_map(fn(Node $argument) => self::_evaluateNode($argument, $variables), $node->nodes['arguments']->nodes);

            return match ($node->attributes['name']) {
                'min' => self::_minMax($arguments, true),
                'max' => self::_minMax($arguments, false),
                default => throw new RuntimeException('Unsupported function.'),
            };
        }

        // Property and method access read browser objects, which a submission doesn't have.
        throw new RuntimeException('Unsupported expression.');
    }

    private static function _evaluateBinary(BinaryNode $node, array $variables): mixed
    {
        $operator = $node->attributes['operator'];
        $left = self::_evaluateNode($node->nodes['left'], $variables);

        // Logical operators return one of their operands, and only read the right side when needed.
        if (in_array($operator, ['or', '||'], true)) {
            return self::isTruthy($left) ? $left : self::_evaluateNode($node->nodes['right'], $variables);
        }

        if (in_array($operator, ['and', '&&'], true)) {
            return self::isTruthy($left) ? self::_evaluateNode($node->nodes['right'], $variables) : $left;
        }

        $right = self::_evaluateNode($node->nodes['right'], $variables);

        return match ($operator) {
            // `i && !r || r && !i`, as the browser package writes it.
            'xor' => self::isTruthy($first = self::isTruthy($right) ? !self::isTruthy($left) : $right)
                ? $first
                : (self::isTruthy($left) ? !self::isTruthy($right) : $left),
            '|' => (float)(self::_toInt32($left) | self::_toInt32($right)),
            '^' => (float)(self::_toInt32($left) ^ self::_toInt32($right)),
            '&' => (float)(self::_toInt32($left) & self::_toInt32($right)),
            '<<' => (float)self::_wrapInt32(self::_toInt32($left) << (self::_toInt32($right) & 31)),
            '>>' => (float)(self::_toInt32($left) >> (self::_toInt32($right) & 31)),
            '==' => self::_looseEquals($left, $right),
            '!=' => !self::_looseEquals($left, $right),
            '===' => self::_strictEquals($left, $right),
            '!==' => !self::_strictEquals($left, $right),
            '<' => self::_lessThan($left, $right),
            '>' => self::_lessThan($right, $left),
            '<=' => self::_lessThanOrEqual($left, $right),
            '>=' => self::_lessThanOrEqual($right, $left),
            'in' => self::_indexOf($right, $left),
            'not in' => !self::_indexOf($right, $left),
            '+' => self::_add($left, $right),
            '-' => self::toNumber($left) - self::toNumber($right),
            '*' => self::toNumber($left) * self::toNumber($right),
            '/' => fdiv(self::toNumber($left), self::toNumber($right)),
            '%' => fmod(self::toNumber($left), self::toNumber($right)),
            '**' => pow(self::toNumber($left), self::toNumber($right)),
            '~' => self::_methodString($left) . self::_methodString($right),
            '..' => self::_range($left, $right),
            'matches' => self::_matches($left, $right),
            'contains' => str_contains(mb_strtolower(self::_methodString($left)), mb_strtolower(self::_methodString($right))),
            'starts with' => str_starts_with(mb_strtolower(self::_methodString($left)), mb_strtolower(self::_methodString($right))),
            'ends with' => str_ends_with(mb_strtolower(self::_methodString($left)), mb_strtolower(self::_methodString($right))),
            default => throw new RuntimeException('Unsupported operator.'),
        };
    }

    private static function _evaluateArray(ArrayNode $node, array $variables): array|stdClass
    {
        $pairs = array_chunk($node->nodes, 2);
        $isList = true;

        foreach ($pairs as $index => [$key]) {
            if (!$key instanceof ConstantNode || $key->attributes['value'] !== $index) {
                $isList = false;
            }
        }

        if ($isList) {
            return array_map(fn(array $pair) => self::_evaluateNode($pair[1], $variables), $pairs);
        }

        $object = new stdClass();

        foreach ($pairs as [$key, $value]) {
            $object->{self::toJsString(self::_evaluateNode($key, $variables))} = self::_evaluateNode($value, $variables);
        }

        return $object;
    }

    private static function _fromPhp(mixed $value): mixed
    {
        if (is_int($value)) {
            return (float)$value;
        }

        if (is_array($value)) {
            return array_is_list($value) ? array_map(self::_fromPhp(...), $value) : (object)array_map(self::_fromPhp(...), $value);
        }

        return $value;
    }

    private static function _add(mixed $left, mixed $right): float|string
    {
        $left = self::_toPrimitive($left);
        $right = self::_toPrimitive($right);

        if (is_string($left) || is_string($right)) {
            return self::toJsString($left) . self::toJsString($right);
        }

        return self::toNumber($left) + self::toNumber($right);
    }

    private static function _toPrimitive(mixed $value): mixed
    {
        return is_array($value) || $value instanceof stdClass ? self::toJsString($value) : $value;
    }

    private static function _looseEquals(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        if (is_array($left) || $left instanceof stdClass || is_array($right) || $right instanceof stdClass) {
            if ((is_array($left) || $left instanceof stdClass) && (is_array($right) || $right instanceof stdClass)) {
                return false;
            }

            return self::_looseEquals(self::_toPrimitive($left), self::_toPrimitive($right));
        }

        if (is_string($left) && is_string($right)) {
            return $left === $right;
        }

        if (is_bool($left) && is_bool($right)) {
            return $left === $right;
        }

        $left = self::toNumber($left);
        $right = self::toNumber($right);

        return !is_nan($left) && !is_nan($right) && $left == $right;
    }

    private static function _strictEquals(mixed $left, mixed $right): bool
    {
        if (is_float($left) && is_float($right)) {
            return !is_nan($left) && $left == $right;
        }

        if (is_array($left) || $left instanceof stdClass) {
            return false;
        }

        return $left === $right;
    }

    private static function _lessThan(mixed $left, mixed $right): bool
    {
        $left = self::_toPrimitive($left);
        $right = self::_toPrimitive($right);

        if (is_string($left) && is_string($right)) {
            return strcmp($left, $right) < 0;
        }

        $left = self::toNumber($left);
        $right = self::toNumber($right);

        return !is_nan($left) && !is_nan($right) && $left < $right;
    }

    private static function _lessThanOrEqual(mixed $left, mixed $right): bool
    {
        $left = self::_toPrimitive($left);
        $right = self::_toPrimitive($right);

        if (is_string($left) && is_string($right)) {
            return strcmp($left, $right) <= 0;
        }

        $left = self::toNumber($left);
        $right = self::toNumber($right);

        return !is_nan($left) && !is_nan($right) && $left <= $right;
    }

    /** `haystack.indexOf(needle) >= 0`, which searches arrays strictly and strings as text. */
    private static function _indexOf(mixed $haystack, mixed $needle): bool
    {
        if (is_array($haystack)) {
            foreach ($haystack as $item) {
                if (self::_strictEquals($item, $needle)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($haystack)) {
            return str_contains($haystack, self::toJsString($needle));
        }

        throw new RuntimeException('indexOf is not a function.');
    }

    /** A value's own `toString()`, which throws for `null` rather than printing it. */
    private static function _methodString(mixed $value): string
    {
        if ($value === null) {
            throw new RuntimeException('Cannot read properties of null.');
        }

        return self::toJsString($value);
    }

    private static function _range(mixed $start, mixed $end): array
    {
        $range = [];
        $end = self::toNumber($end);

        for ($i = self::toNumber($start); $i <= $end && count($range) < 100000; $i++) {
            $range[] = $i;
        }

        return $range;
    }

    private static function _matches(mixed $subject, mixed $pattern): bool
    {
        if ($subject === null) {
            return false;
        }

        if (!is_string($pattern) || !preg_match('/\/(.+)\/(.*)/s', $pattern, $match)) {
            throw new RuntimeException('Invalid regular expression.');
        }

        $flags = implode('', array_intersect(str_split($match[2] ?: ''), ['i', 'm', 's', 'u']));
        $result = @preg_match('~' . str_replace('~', '\~', $match[1]) . '~' . $flags, self::toJsString($subject));

        if ($result === false) {
            throw new RuntimeException('Invalid regular expression.');
        }

        return $result === 1;
    }

    private static function _minMax(array $arguments, bool $min): float
    {
        $result = $min ? INF : -INF;

        foreach ($arguments as $argument) {
            $number = self::toNumber($argument);

            if (is_nan($number)) {
                return NAN;
            }

            $result = $min ? min($result, $number) : max($result, $number);
        }

        return $result;
    }

    private static function _toInt32(mixed $value): int
    {
        $number = self::toNumber($value);

        if (is_nan($number) || is_infinite($number)) {
            return 0;
        }

        return self::_wrapInt32((int)fmod($number < 0 ? ceil($number) : floor($number), 4294967296.0));
    }

    private static function _wrapInt32(int $value): int
    {
        $value &= 0xFFFFFFFF;

        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }

    private static function _trim(string $value): string
    {
        return preg_replace('/^[\s\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+|[\s\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+$/u', '', $value) ?? trim($value);
    }

    private static function _incrementDigits(string $digits): string
    {
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            if ($digits[$i] !== '9') {
                return substr($digits, 0, $i) . ((int)$digits[$i] + 1) . str_repeat('0', strlen($digits) - $i - 1);
            }
        }

        return '1' . str_repeat('0', strlen($digits));
    }
}
