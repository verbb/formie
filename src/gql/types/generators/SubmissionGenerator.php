<?php
namespace verbb\formie\gql\types\generators;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\types\SubmissionType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\helpers\Gql as GqlHelper;

class SubmissionGenerator implements GeneratorInterface
{
    // Public Methods
    // =========================================================================

    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];

        foreach (Form::find()->all() as $form) {
            $typeName = Submission::gqlTypeNameByContext($form);

            $contentFields = $form->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $submissionFields = TypeManager::prepareFieldDefinitions(array_merge(SubmissionInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new SubmissionType([
                'name' => $typeName,
                'fields' => function() use ($submissionFields) {
                    return $submissionFields;
                }
            ]));
        }

        return $gqlTypes;
    }
}
