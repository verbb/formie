<?php
namespace verbb\formie\gql\types;

use verbb\formie\gql\interfaces\PageInterface;

use craft\gql\base\ObjectType;

use GraphQL\Type\Definition\ResolveInfo;

class PageType extends ObjectType
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            PageInterface::getType(),
        ];

        parent::__construct($config);
    }

    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        return $source[$resolveInfo->fieldName];
    }
}
