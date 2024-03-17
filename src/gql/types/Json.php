<?php
namespace verbb\formie\gql\types;

use craft\errors\GqlException;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json as JsonHelper;

use GraphQL\Type\Definition\ScalarType;

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
        return JsonHelper::decode($value);
    }

    public function parseLiteral($valueNode, array $variables = null)
    {
        if (!property_exists($valueNode, 'value')) {
            throw new GqlException("Can not parse literals without a value: {$withoutValue}.");
        }

        return $this->parseValue($valueNode->value);
    }
}