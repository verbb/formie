<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class FormServerRenderedPayloadType extends ObjectType
{
    public static function getName(): string
    {
        return 'FormieServerRenderedPayloadType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'html' => [
                    'name' => 'html',
                    'type' => Type::string(),
                    'description' => 'Rendered HTML for server-rendered requests.',
                ],
            ],
        ]));
    }
}
