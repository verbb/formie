<?php
namespace verbb\formie\gql\types;

use Craft;
use craft\gql\base\ObjectType;

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

        return Craft::$app->getGql()->prepareFieldDefinitions($contentFields, $typeName);
    }
}
