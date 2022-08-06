<?php
namespace verbb\formie\gql\arguments;

use craft\gql\base\ElementArguments;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'form' => [
                'name' => 'form',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the submission’s form handle.',
            ],
            'isIncomplete' => [
                'name' => 'isIncomplete',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on the submission’s incomplete state.',
            ],
            'isSpam' => [
                'name' => 'isSpam',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on the submission’s spam state.',
            ],
        ]);
    }
}
