<?php
namespace verbb\formie\gql\types;

use craft\helpers\Gql;
use verbb\formie\gql\interfaces\FormInterface;

use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\types\elements\Element;

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

    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        $fieldName = Gql::getFieldNameWithAlias($resolveInfo, $source, $context);

        switch ($fieldName) {
            case 'formFields':
                return $source->getFields();
        }

        return $source[$resolveInfo->fieldName];
    }
}
