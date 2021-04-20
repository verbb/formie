<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;

use GraphQL\Type\Definition\Type;

class FormIntegrationsType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormIntegrationsType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'name' => [
                    'name' => 'name',
                    'type' => Type::string(),
                ],
                'handle' => [
                    'name' => 'handle',
                    'type' => Type::string(),
                ],
                'enabled' => [
                    'name' => 'enabled',
                    'type' => Type::boolean(),
                ],
                'settings' => [
                    'name' => 'settings',
                    'type' => Type::string(),
                    'resolve' => function ($source, $arguments) {
                        return Json::encode($source);
                    },
                ],
            ],
        ]));
    }
}
