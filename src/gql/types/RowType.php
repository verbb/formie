<?php
namespace verbb\formie\gql\types;

use verbb\formie\gql\interfaces\RowInterface;

use craft\gql\base\ObjectType;
use craft\helpers\Gql;

use GraphQL\Type\Definition\ResolveInfo;

class RowType extends ObjectType
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            RowInterface::getType(),
        ];

        parent::__construct($config);
    }

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = Gql::getFieldNameWithAlias($resolveInfo, $source, $context);

        $fields = $source['fields'] ?? [];
        $includeDisabled = $arguments['includeDisabled'] ?? false;

        // Don't include disabled fields by default for GQL
        if (!$includeDisabled) {
            $fields = array_filter($fields, function($field) {
                return $field->visibility !== 'disabled';
            });
        }

        return match ($fieldName) {
            'rowFields' => $fields,
            default => $source[$resolveInfo->fieldName],
        };
    }
}
