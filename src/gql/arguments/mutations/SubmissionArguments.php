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
            'expectedVersion' => ['name' => 'expectedVersion', 'type' => Type::int(), 'description' => 'Required current state version when revising a submission.'],
            'operationId' => ['name' => 'operationId', 'type' => Type::string(), 'description' => 'Stable identity for retrying this operation.'],
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
            'requestToken' => [
                'name' => 'requestToken',
                'type' => Type::string(),
                'description' => 'Optional request token for duplicate-submit/replay protection.',
            ],
        ]);
    }
}
