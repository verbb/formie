<?php
namespace verbb\formie\gql\queries;

use verbb\formie\gql\resolvers\ClientFormResolver;
use verbb\formie\gql\types\ClientFormBootstrapType;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class ClientFormQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryForms()) {
            return [];
        }

        return [
            'formieClientForm' => [
                'type' => ClientFormBootstrapType::getType(),
                'args' => [
                    'handle' => Type::nonNull(Type::string()),
                    'siteId' => Type::int(),
                    'locale' => Type::string(),
                ],
                'resolve' => ClientFormResolver::class . '::resolveForm',
                'description' => 'Return the canonical client form bootstrap payload.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
        ];
    }
}
