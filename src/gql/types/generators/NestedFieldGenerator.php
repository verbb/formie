<?php
namespace verbb\formie\gql\types\generators;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\ObjectType;

class NestedFieldGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function generateType(mixed $context): mixed
    {
        $typeName = $context::gqlTypeNameByContext($context);

        if (!($entity = GqlEntityRegistry::getEntity($typeName))) {
            $groupFields = self::getContentFields($context);

            $entity = GqlEntityRegistry::createEntity($typeName, new ObjectType([
                'name' => $typeName,
                'fields' => function() use ($groupFields, $typeName) {
                    return Craft::$app->getGql()->prepareFieldDefinitions($groupFields, $typeName);
                },
            ]));
        }

        return $entity;
    }


    // Protected Methods
    // =========================================================================

    protected static function getContentFields($context): array
    {
        try {
            $schema = Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException $e) {
            Craft::warning("Could not get the active GraphQL schema: {$e->getMessage()}", __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return [];
        }

        $contentFieldGqlTypes = [];

        // Handle form fields
        foreach ($context->getFields() as $contentField) {
            if ($contentField->includeInGqlSchema($schema)) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }
        }

        return $contentFieldGqlTypes;
    }
}
