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
                'quizResult' => [
                    'name' => 'quizResult',
                    'type' => ArrayType::getType(),
                ],
                'clientEvents' => [
                    'name' => 'clientEvents',
                    'type' => ArrayType::getType(),
                ],
                'paymentStatus' => [
                    'name' => 'paymentStatus',
                    'type' => Type::string(),
                    'description' => 'The payment follow-up status when a payment provider requires additional action.',
                ],
                'paymentMessage' => [
                    'name' => 'paymentMessage',
                    'type' => Type::string(),
                    'description' => 'A user-facing message describing the required payment follow-up.',
                ],
                'paymentRedirectUrl' => [
                    'name' => 'paymentRedirectUrl',
                    'type' => Type::string(),
                    'description' => 'A redirect URL when the payment provider requires off-site completion.',
                ],
                'paymentAction' => [
                    'name' => 'paymentAction',
                    'type' => Json::getType(),
                    'description' => 'Structured payment follow-up action metadata for headless front-ends.',
                ],
                'paymentDecision' => [
                    'name' => 'paymentDecision',
                    'type' => Json::getType(),
                    'description' => 'The canonical payment decision payload returned by the submission workflow.',
                ],
                'keepSubmitLoading' => [
                    'name' => 'keepSubmitLoading',
                    'type' => Type::boolean(),
                    'description' => 'Whether the front-end should keep the submit state active while payment follow-up runs.',
                ],
            ],
        ]));
    }
}
