<?php
namespace verbb\formie\gql\queries;

use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\resolvers\SubmissionResolver;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class SubmissionQuery extends Query
{
    // Public Methods
    // =========================================================================

    public static function getQueries($checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQuerySubmissions()) {
            return [];
        }
        
        return [
            'formieSubmissions' => [
                'type' => Type::listOf(SubmissionInterface::getType()),
                'args' => SubmissionArguments::getArguments(),
                'resolve' => SubmissionResolver::class . '::resolve',
                'description' => 'This query is used to query for submissions.',
            ],
            'formieSubmission' => [
                'type' => SubmissionInterface::getType(),
                'args' => SubmissionArguments::getArguments(),
                'resolve' => SubmissionResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single submission.',
            ],
            'submissions' => [
                'type' => Type::listOf(SubmissionInterface::getType()),
                'args' => SubmissionArguments::getArguments(),
                'resolve' => SubmissionResolver::class . '::resolve',
                'description' => 'This query is used to query for submissions.',
            ],
            'submission' => [
                'type' => SubmissionInterface::getType(),
                'args' => SubmissionArguments::getArguments(),
                'resolve' => SubmissionResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single submission.',
            ],
        ];
    }
}
