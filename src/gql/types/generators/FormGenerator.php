<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\FormArguments;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\types\FormType;

use Craft;
use craft\errors\GqlException;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\helpers\Gql as GqlHelper;

class FormGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $forms = Formie::getInstance()->getForms()->getAllForms();
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
    public static function generateType($context): ObjectType
    {
        $typeName = Form::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);

        $formFields = TypeManager::prepareFieldDefinitions(array_merge(FormInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new FormType([
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
            foreach ($fieldLayout->getFields() as $contentField) {
                if ($contentField->includeInGqlSchema($schema)) {
                    $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
                }
            }
        }

        return $contentFieldGqlTypes;
    }
}
