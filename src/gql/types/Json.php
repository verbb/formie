<?php
namespace verbb\formie\gql\types;

use craft\gql\GqlEntityRegistry;
use craft\helpers\Json as JsonHelper;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\AST;

class Json extends ScalarType
{
    public static function getType()
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn() => new self());
    }

    public static function getName(): string
    {
        return 'Json';
    }

    public function serialize($value): string
    {
        return JsonHelper::encode($value);
    }

    public function parseValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return JsonHelper::decode($value);
    }

    public function parseLiteral($valueNode, array $variables = null)
    {
        return AST::valueFromASTUntyped($valueNode, $variables);
    }
}