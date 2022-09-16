<?php
namespace verbb\formie\gql\resolvers\elements;

use verbb\formie\elements\NestedFieldRow;

use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Gql;

use GraphQL\Type\Definition\ResolveInfo;

class NestedFieldRowResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        // If this is the beginning of a resolver chain, start fresh
        if ($source === null) {
            $query = NestedFieldRow::find();
        } else {
            // If not, get the prepared element query
            $query = $source->$fieldName;
        }

        // If it's preloaded, it's preloaded.
        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        return $query;
    }

    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
        $value = $query instanceof ElementQuery ? $query->one() : $query;

        return Gql::applyDirectives($source, $resolveInfo, $value);
    }
}
