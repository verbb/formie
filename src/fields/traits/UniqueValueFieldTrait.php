<?php
namespace verbb\formie\fields\traits;

use verbb\formie\helpers\SchemaHelper;

use GraphQL\Type\Definition\Type;

trait UniqueValueFieldTrait
{
    // Properties
    // =========================================================================

    public bool $uniqueValue = false;


    // Protected Methods
    // =========================================================================

    protected function defineUniqueValueGqlType(): array
    {
        return [
            'uniqueValue' => [
                'name' => 'uniqueValue',
                'type' => Type::boolean(),
            ],
        ];
    }

    protected function defineUniqueValueValidationSchema(): array
    {
        return [
            SchemaHelper::uniqueValueField(),
            SchemaHelper::uniqueValidationMessage(),
        ];
    }

    protected function defineUniqueValueRules(): array
    {
        return [
            ['uniqueValue', 'boolean'],
        ];
    }

    protected function getUniqueValueElementValidationRules(): array
    {
        if (!$this->uniqueValue) {
            return [];
        }

        return [
            [$this->handle, 'validateUniqueValue'],
        ];
    }
}
