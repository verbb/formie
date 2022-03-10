<?php
namespace verbb\formie\gql\arguments;

use craft\gql\base\ElementArguments;

use GraphQL\Type\Definition\Type;

class FormArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'handle' => [
                'name' => 'handle',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the form’s handle.',
            ],
        ]);
    }
}
