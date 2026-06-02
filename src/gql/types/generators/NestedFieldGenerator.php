<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\Formie;

use Craft;
use craft\errors\GqlException;
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

    public static function generateTypeFromConfig(array $context): mixed
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($context, 'NestedField');

        if (!($entity = GqlEntityRegistry::getEntity($typeName))) {
            $groupFields = self::getContentFieldsFromConfig($context);

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
        $fieldsService = Formie::$plugin->getFields();
        $contentFields = method_exists($context, 'getFields') ? $context->getFields() : [];

        foreach ($contentFields as $contentField) {
            if ($fieldsService->fieldIncludedInGqlSchema($contentField, $schema)) {
                $contentFieldGqlTypes[$contentField->handle] = $fieldsService->getFieldContentGqlType($contentField);
            }
        }

        return $contentFieldGqlTypes;
    }

    protected static function getContentFieldsFromConfig(array $context): array
    {
        try {
            $schema = Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException $e) {
            Craft::warning("Could not get the active GraphQL schema: {$e->getMessage()}", __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return [];
        }

        $contentFieldGqlTypes = [];
        $fieldsService = Formie::$plugin->getFields();
        $contentFields = $fieldsService->getNestedFieldConfigs($context);

        foreach ($contentFields as $contentField) {
            $handle = $contentField['handle'] ?? null;

            if (!$handle) {
                continue;
            }

            if ($fieldsService->fieldConfigIncludedInGqlSchema($contentField, $schema)) {
                $contentFieldGqlTypes[$handle] = $fieldsService->getFieldConfigContentGqlType($contentField);
            }
        }

        return $contentFieldGqlTypes;
    }
}
