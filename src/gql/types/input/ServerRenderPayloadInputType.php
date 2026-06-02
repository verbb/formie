<?php
namespace verbb\formie\gql\types\input;

use verbb\formie\gql\types\Json as JsonType;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class ServerRenderPayloadInputType extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'ServerRenderPayloadInput';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return [
                    'theme' => [
                        'name' => 'theme',
                        'type' => Type::string(),
                    ],
                    'themeConfig' => [
                        'name' => 'themeConfig',
                        'type' => JsonType::getType(),
                    ],
                    'locale' => [
                        'name' => 'locale',
                        'type' => Type::string(),
                    ],
                    'siteId' => [
                        'name' => 'siteId',
                        'type' => Type::int(),
                    ],
                ];
            },
        ]));
    }
}
