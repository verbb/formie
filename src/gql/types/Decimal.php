<?php
namespace verbb\formie\gql\types;

use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\NullValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

final class Decimal extends ScalarType implements SingularTypeInterface
{
    // Static Methods
    // =========================================================================

    public static function getType(): self
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self());
    }

    public static function getName(): string
    {
        return 'FormieDecimal';
    }


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        parent::__construct([
            'name' => self::getName(),
            'description' => 'Decimal text without floating-point conversion. Send string variables to preserve every digit.',
        ] + $config);
    }

    public function serialize($value): ?string
    {
        return $this->parseValue($value);
    }

    public function parseValue($value): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        if (is_int($value) || (is_float($value) && is_finite($value))) {
            // Numeric variables have already been decoded by the JSON transport.
            return (string)$value;
        }

        throw new Error('FormieDecimal accepts decimal text, a finite number, or null.');
    }

    public function parseLiteral($valueNode, ?array $variables = null): ?string
    {
        if ($valueNode instanceof NullValueNode) {
            return null;
        }

        if ($valueNode instanceof StringValueNode || $valueNode instanceof FloatValueNode || $valueNode instanceof IntValueNode) {
            return $valueNode->value;
        }

        throw new Error('FormieDecimal accepts decimal text, a number, or null.');
    }
}
