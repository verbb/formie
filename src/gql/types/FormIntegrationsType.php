<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use craft\helpers\App;
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
                    'description' => 'The integration’s name.',
                ],
                'handle' => [
                    'name' => 'handle',
                    'type' => Type::string(),
                    'description' => 'The integration’s handle.',
                ],
                'enabled' => [
                    'name' => 'enabled',
                    'type' => Type::boolean(),
                    'description' => 'Whether the integration is enabled.',
                ],
                'settings' => [
                    'name' => 'settings',
                    'type' => Type::string(),
                    'description' => 'The integration’s settings as a JSON string.',
                    'resolve' => function($source, $arguments) {
                        if ($settings = $source->allowedGqlSettings()) {
                            return Json::encode($settings);
                        }

                        return null;
                    },
                ],
            ],
        ]));
    }
}
