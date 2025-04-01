<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\SingleOptionFieldData;

use craft\gql\base\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class OptionFieldResolver extends Resolver
{
    // Static Methods
    // =========================================================================

    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;
        $optionFieldData = $source->{$fieldName};

        $resolvedValue = '';
        $label = !empty($arguments['label']);

        if ($optionFieldData instanceof MultiOptionsFieldData) {
            $resolvedValue = [];

            foreach ($optionFieldData as $optionData) {
                $resolvedValue[] = $label ? $optionData->label : $optionData->value;
            }
        } elseif ($optionFieldData instanceof SingleOptionFieldData) {
            $resolvedValue = $label ? $optionFieldData->label : $optionFieldData->value;
        }

        return $resolvedValue;
    }
}
