<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class CaptchaValueType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormieCaptchaType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'handle' => [
                    'name' => 'handle',
                    'type' => Type::string(),
                    'description' => 'The captcha handle.',
                ],
                'name' => [
                    'name' => 'name',
                    'type' => Type::string(),
                    'description' => 'The captcha name.',
                ],
                'value' => [
                    'name' => 'value',
                    'type' => Type::string(),
                    'description' => 'The catpcha value.',
                ],
            ],
        ]));
    }
}
