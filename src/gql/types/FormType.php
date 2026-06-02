<?php
namespace verbb\formie\gql\types;

use verbb\formie\fields\MissingField;
use verbb\formie\gql\interfaces\FormInterface;

use craft\gql\types\elements\Element;
use craft\helpers\Gql;

use GraphQL\Type\Definition\ResolveInfo;

class FormType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            FormInterface::getType(),
        ];

        parent::__construct($config);
    }

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = Gql::getFieldNameWithAlias($resolveInfo, $source, $context);

        return match ($fieldName) {
            'formFields' => $this->_resolveFields($source->getFields(), $arguments['includeDisabled'] ?? false),
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
