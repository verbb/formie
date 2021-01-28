<?php
namespace verbb\formie\gql\types;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\ScalarType;

class ArrayType extends ScalarType
{
    // Static Methods
    // =========================================================================

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self());
    }

    public static function getName(): string
    {
        return 'ArrayType';
    }


    // Public Methods
    // =========================================================================

    public function serialize($value)
    {
        if (!is_array($value)) {
            $value->toArray();
        }

        return $value;
    }

    public function parseValue($value)
    {
        return $value;
    }

    public function parseLiteral($valueNode, array $variables = null)
    {
        return $valueNode;
    }
}
