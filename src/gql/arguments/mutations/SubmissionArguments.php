<?php
namespace verbb\formie\gql\arguments\mutations;

use craft\gql\base\ElementMutationArguments;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementMutationArguments
{
    // Static Methods
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
            'isIncomplete' => [
                'name' => 'isIncomplete',
                'type' => Type::boolean(),
                'description' => 'The submission’s incomplete state.',
            ],
        ]);
    }
}
