<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class NameType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'NameType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'prefix' => [
                    'name' => 'prefix',
                    'type' => Type::string(),
                    'description' => 'The prefix value of the name.',
                ],
                'firstName' => [
                    'name' => 'firstName',
                    'type' => Type::string(),
                    'description' => 'The first name value of the name.',
                ],
                'middleName' => [
                    'name' => 'middleName',
                    'type' => Type::string(),
                    'description' => 'The middle name value of the name.',
                ],
                'lastName' => [
                    'name' => 'lastName',
                    'type' => Type::string(),
                    'description' => 'The last name value of the name.',
                ],
            ],
        ]));
    }
}
