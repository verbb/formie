<?php
namespace verbb\formie\gql\queries;

use verbb\formie\gql\arguments\FormArguments;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\resolvers\FormResolver;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class FormQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryForms()) {
            return [];
        }

        return [
            'formieForms' => [
                'type' => Type::listOf(FormInterface::getType()),
                'args' => FormArguments::getArguments(),
                'resolve' => FormResolver::class . '::resolve',
                'description' => 'This query is used to query for forms.',
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ],
            'formieForm' => [
                'type' => FormInterface::getType(),
                'args' => FormArguments::getArguments(),
                'resolve' => FormResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single form.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'formieFormCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => FormArguments::getArguments(),
                'resolve' => FormResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of forms.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
        ];
    }
}
