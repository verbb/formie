<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\gql\types\TableRowType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

class TableRowTypeGenerator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName($context = null): string
    {
        return $context->handle . '_TableRow';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new TableRowType([
            'name' => $typeName,
            'fields' => function() use ($context, $typeName) {
                $contentFields = TableRowType::prepareRowFieldDefinition($context);

                return Craft::$app->getGql()->prepareFieldDefinitions($contentFields, $typeName);
            },
        ]));
    }
}
