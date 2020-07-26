<?php
namespace verbb\formie\gql\arguments;

use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementArguments
{
    // Public Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'form' => [
                'name' => 'form',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the submission’s form handle.'
            ],
        ]);
    }
}
