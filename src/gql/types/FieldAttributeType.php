<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\TypeManager;

use GraphQL\Type\Definition\Type;

class FieldAttributeType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function prepareRowFieldDefinition(string $typeName): array
    {
        $contentFields = [
            'label' => Type::string(),
            'value' => Type::string(),
        ];

        return TypeManager::prepareFieldDefinitions($contentFields, $typeName);
    }
}
