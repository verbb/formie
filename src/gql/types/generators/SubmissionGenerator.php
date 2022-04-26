<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\types\SubmissionType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class SubmissionGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
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
            $requiredContexts = Submission::gqlScopesByContext($form);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts) && !GqlHelper::isSchemaAwareOf('formieSubmissions.all')) {
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
        $typeName = Submission::gqlTypeNameByContext($context);

        if ($createdType = GqlEntityRegistry::getEntity($typeName)) {
            return $createdType;
        }

        $contentFieldGqlTypes = self::getContentFields($context);
        $submissionFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(SubmissionInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::createEntity($typeName, new SubmissionType([
            'name' => $typeName,
            'fields' => function() use ($submissionFields) {
                return $submissionFields;
            },
        ]));
    }
}
