<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class ClientSubmitResultType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormieClientSubmitResultType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'success' => [
                    'name' => 'success',
                    'type' => Type::nonNull(Type::boolean()),
                ],
                'submissionUid' => [
                    'name' => 'submissionUid',
                    'type' => Type::string(),
                ],
                'currentPageId' => [
                    'name' => 'currentPageId',
                    'type' => Type::string(),
                ],
                'nextPageId' => [
                    'name' => 'nextPageId',
                    'type' => Type::string(),
                ],
                'previousPageId' => [
                    'name' => 'previousPageId',
                    'type' => Type::string(),
                ],
                'isFinalPage' => [
                    'name' => 'isFinalPage',
                    'type' => Type::nonNull(Type::boolean()),
                ],
                'errors' => [
                    'name' => 'errors',
                    'type' => ArrayType::getType(),
                ],
                'messages' => [
                    'name' => 'messages',
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
