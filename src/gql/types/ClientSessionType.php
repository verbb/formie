<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class ClientSessionType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormieClientSessionType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'id' => [
                    'name' => 'id',
                    'type' => Type::nonNull(Type::string()),
                ],
                'currentPageId' => [
                    'name' => 'currentPageId',
                    'type' => Type::nonNull(Type::string()),
                ],
                'tokens' => [
                    'name' => 'tokens',
                    'type' => ArrayType::getType(),
                ],
                'continuation' => [
                    'name' => 'continuation',
                    'type' => ArrayType::getType(),
                ],
            ],
        ]));
    }
}
