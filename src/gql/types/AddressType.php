<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class AddressType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'AddressType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'fullAddress' => [
                    'name' => 'fullAddress',
                    'type' => Type::string(),
                    'description' => 'The full address value.',
                    'resolve' => function($value) {
                        return (string)$value;
                    },
                ],
                'address1' => [
                    'name' => 'address1',
                    'type' => Type::string(),
                    'description' => 'The address1 value of the address.',
                ],
                'address2' => [
                    'name' => 'address2',
                    'type' => Type::string(),
                    'description' => 'The address2 value of the address.',
                ],
                'address3' => [
                    'name' => 'address3',
                    'type' => Type::string(),
                    'description' => 'The address3 value of the address.',
                ],
                'city' => [
                    'name' => 'city',
                    'type' => Type::string(),
                    'description' => 'The city value of the address.',
                ],
                'state' => [
                    'name' => 'state',
                    'type' => Type::string(),
                    'description' => 'The state value of the address.',
                ],
                'zip' => [
                    'name' => 'zip',
                    'type' => Type::string(),
                    'description' => 'The zip value of the address.',
                ],
                'country' => [
                    'name' => 'country',
                    'type' => Type::string(),
                    'description' => 'The country value of the address.',
                ],
            ],
        ]));
    }
}
