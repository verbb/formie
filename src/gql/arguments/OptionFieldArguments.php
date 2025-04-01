<?php
namespace verbb\formie\gql\arguments;

use craft\gql\base\Arguments;

use GraphQL\Type\Definition\Type;

class OptionFieldArguments extends Arguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return [
            'label' => [
                'name' => 'label',
                'type' => Type::boolean(),
                'description' => 'If set to true, will return label instead of the value',
            ],
        ];
    }
}
