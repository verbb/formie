<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class ClientFormBootstrapType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormieClientFormBootstrapType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'schemaVersion' => [
                    'name' => 'schemaVersion',
                    'type' => Type::nonNull(Type::int()),
                ],
                'definition' => [
                    'name' => 'definition',
                    'type' => ArrayType::getType(),
                ],
                'session' => [
                    'name' => 'session',
                    'type' => ClientSessionType::getType(),
                ],
            ],
        ]));
    }
}
