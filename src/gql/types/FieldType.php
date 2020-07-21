<?php
namespace verbb\formie\gql\types;

use verbb\formie\gql\interfaces\FieldInterface;

use craft\gql\base\ObjectType;

use GraphQL\Type\Definition\ResolveInfo;

class FieldType extends ObjectType
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            FieldInterface::getType(),
        ];

        parent::__construct($config);
    }

    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        return $source[$resolveInfo->fieldName];
    }
}
