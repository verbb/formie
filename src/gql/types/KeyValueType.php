<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;

use GraphQL\Type\Definition\ResolveInfo;

class KeyValueType extends ObjectType
{
    // Public Methods
    // =========================================================================

    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        return $source[$resolveInfo->fieldName];
    }
}
