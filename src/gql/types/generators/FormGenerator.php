<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\types\FormType;

use Craft;
use craft\errors\GqlException;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class FormGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        $forms = Formie::$plugin->getForms()->getAllForms();
        $gqlTypes = [];

        foreach ($forms as $form) {
            $requiredContexts = Form::gqlScopesByContext($form);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts) && !GqlHelper::isSchemaAwareOf('formieForms.all')) {
                continue;
            }

            $type = static::generateType($form);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): mixed
    {
        $typeName = Form::gqlTypeNameByContext($context);

        if ($createdType = GqlEntityRegistry::getEntity($typeName)) {
            return $createdType;
        }

        $contentFieldGqlTypes = self::getContentFields($context);
        $formFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(FormInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::createEntity($typeName, new FormType([
            'name' => $typeName,
            'fields' => function() use ($formFields) {
                return $formFields;
            },
        ]));
    }


    // Protected Methods
    // =========================================================================

    /**
     * Get content fields for a given context.
     *
     * @param mixed $context
     * @return array
     */
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

        if ($fieldLayout = $context->getFieldLayout()) {
            /** @var Field $contentField */
            foreach ($fieldLayout->getCustomFields() as $contentField) {
                if ($contentField->includeInGqlSchema($schema)) {
                    $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
                }
            }
        }

        return $contentFieldGqlTypes;
    }
}
