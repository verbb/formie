<?php
namespace verbb\formie\gql\arguments\mutations;

use craft\gql\base\ElementMutationArguments;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementMutationArguments
{
    // Public Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The submission’s status.',
            ],
            'statusId' => [
                'name' => 'statusId',
                'type' => Type::int(),
                'description' => 'The submission’s status ID.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'The submission’s site ID.',
            ],
        ]);
    }
}
