<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class CsrfTokenType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormieCsrfTokenType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'name' => [
                    'name' => 'name',
                    'type' => Type::string(),
                    'description' => 'The CSRF name.',
                ],
                'value' => [
                    'name' => 'value',
                    'type' => Type::string(),
                    'description' => 'The CSRF token.',
                ],
            ],
        ]));
    }
}
