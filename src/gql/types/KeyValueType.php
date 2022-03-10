<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;

use GraphQL\Type\Definition\ResolveInfo;

class KeyValueType extends ObjectType
{
    // Public Methods
    // =========================================================================

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        return $source[$resolveInfo->fieldName];
    }
}
