<?php
namespace verbb\formie\gql\types;

use verbb\formie\fields\Table;

use craft\gql\base\ObjectType;
use craft\gql\types\DateTime;
use craft\gql\types\Number;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class TableRowType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function prepareRowFieldDefinition(Table $field): array
    {
        $contentFields = [];

        foreach ($field->columns as $columnDefinition) {
            $cellType = match ($columnDefinition['type']) {
                'date', 'time' => DateTime::getType(),
                'number' => Number::getType(),
                'lightswitch' => Type::boolean(),
                default => Type::string(),
            };

            $columnKey = $columnDefinition['handle'] ?? null;

            if ($columnKey) {
                $contentFields[$columnKey] = $cellType;
            }
        }

        return $contentFields;
    }


    // Protected Methods
    // =========================================================================

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;

        return $source[$fieldName];
    }
}
