<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\gql\types\ArrayType;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class ClientSessionRefreshInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(): mixed
    {
        $typeName = 'FormieClientSessionRefreshInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => [
                'handle' => [
                    'name' => 'handle',
                    'type' => Type::nonNull(Type::string()),
                ],
                'siteId' => [
                    'name' => 'siteId',
                    'type' => Type::int(),
                ],
                'session' => [
                    'name' => 'session',
                    'type' => ArrayType::getType(),
                ],
            ],
        ]));
    }
}
