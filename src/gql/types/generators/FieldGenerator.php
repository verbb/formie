<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\Formie;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\fields\MissingField;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\FieldType;

use Craft;
use craft\errors\GqlException;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\Type;

class FieldGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $fields = Formie::$plugin->getFields()->getRegisteredFields();

        $gqlTypes = [];

        try {
            $schema = Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException $e) {
            Craft::warning("Could not get the active GraphQL schema: {$e->getMessage()}", __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
            return [];
        }

        foreach ($fields as $field) {
            if ($field instanceof MissingField) {
                continue;
            }
            $typeName = $field->getGqlTypeName();
            $contentFieldGqlTypes = $field->getSettingGqlTypes();

            if (!$field->includeInGqlSchema($schema)) {
                continue;
            }

            $fieldFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(FieldInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new FieldType([
                'name' => $typeName,
                'fields' => function() use ($fieldFields) {
                    return $fieldFields;
                },
            ]));
        }

        return $gqlTypes;
    }
}
