<?php
namespace verbb\formie\gql\types;

use verbb\formie\fields\MissingField;
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

        return match ($fieldName) {
            'rowFields' => $this->_resolveFields($source['fields'] ?? [], $arguments['includeDisabled'] ?? false),
            default => $source[$resolveInfo->fieldName],
        };
    }

    private function _resolveFields(array $fields, bool $includeDisabled): array
    {
        $fields = array_filter($fields, fn($field) => !($field instanceof MissingField));

        if (!$includeDisabled) {
            $fields = array_filter($fields, fn($field) => $field->visibility !== 'disabled');
        }

        return $fields;
    }
}
