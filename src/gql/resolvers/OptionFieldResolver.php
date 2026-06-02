<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;

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

        if ($optionFieldData instanceof MultiOptionFieldValue) {
            $resolvedValue = [];

            foreach ($optionFieldData as $optionData) {
                $resolvedValue[] = $label ? $optionData->label : $optionData->value;
            }
        } elseif ($optionFieldData instanceof SingleOptionFieldValue) {
            $resolvedValue = $label ? $optionFieldData->label : $optionFieldData->value;
        }

        return $resolvedValue;
    }
}
