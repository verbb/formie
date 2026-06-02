<?php
namespace verbb\formie\gql\mutations;

use verbb\formie\gql\resolvers\ClientFormResolver;
use verbb\formie\gql\types\ClientSessionType;
use verbb\formie\gql\types\ClientSubmitResultType;
use verbb\formie\gql\types\input\ClientSessionRefreshInputType;
use verbb\formie\gql\types\input\ClientSetPageInputType;
use verbb\formie\gql\types\input\ClientSubmitInputType;

use craft\gql\base\Mutation;

use GraphQL\Type\Definition\Type;

class ClientFormMutation extends Mutation
{
    // Static Methods
    // =========================================================================

    public static function getMutations(): array
    {
        return [
            'refreshFormieClientSession' => [
                'name' => 'refreshFormieClientSession',
                'description' => 'Refresh the canonical client form session.',
                'args' => [
                    'input' => Type::nonNull(ClientSessionRefreshInputType::getType()),
                ],
                'resolve' => ClientFormResolver::class . '::refreshSession',
                'type' => ClientSessionType::getType(),
            ],
            'setFormieClientPage' => [
                'name' => 'setFormieClientPage',
                'description' => 'Persist canonical client page navigation state.',
                'args' => [
                    'input' => Type::nonNull(ClientSetPageInputType::getType()),
                ],
                'resolve' => ClientFormResolver::class . '::setPage',
                'type' => ClientSessionType::getType(),
            ],
            'submitFormieClientForm' => [
                'name' => 'submitFormieClientForm',
                'description' => 'Submit a canonical client form payload.',
                'args' => [
                    'input' => Type::nonNull(ClientSubmitInputType::getType()),
                ],
                'resolve' => ClientFormResolver::class . '::submitForm',
                'type' => ClientSubmitResultType::getType(),
            ],
        ];
    }
}
