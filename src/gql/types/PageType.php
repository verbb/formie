<?php
namespace verbb\formie\gql\types;

use verbb\formie\gql\interfaces\PageInterface;

use craft\gql\base\ObjectType;
use craft\helpers\Gql;

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

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = Gql::getFieldNameWithAlias($resolveInfo, $source, $context);

        return match ($fieldName) {
            'pageFields' => $source->getCustomFields(),
            default => $source[$resolveInfo->fieldName],
        };
    }
}
