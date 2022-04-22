<?php
namespace verbb\formie\gql\types;

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
            'formFields' => $source->getCustomFields(),
            default => $source[$resolveInfo->fieldName],
        };
    }
}
