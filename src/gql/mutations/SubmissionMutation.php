<?php
namespace verbb\formie\gql\mutations;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\mutations\SubmissionArguments as SubmissionMutationArguments;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
use verbb\formie\gql\types\generators\SubmissionGenerator;

use Craft;
use craft\gql\base\ElementMutationArguments;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;

use GraphQL\Type\Definition\Type;

class SubmissionMutation extends Mutation
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        $mutationList = [];

        $createDeleteMutation = false;

        foreach (Form::find()->all() as $form) {
            $mutation = static::createSaveMutation($form);
            $mutationList[$mutation['name']] = $mutation;

            if (!$createDeleteMutation) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteSubmission'] = [
                'name' => 'deleteSubmission',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [Craft::createObject(SubmissionResolver::class), 'deleteSubmission'],
                'description' => 'Delete a submission.',
                'type' => Type::boolean(),
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-form save mutation.
     *
     * @param Form $form
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function createSaveMutation(Form $form): array
    {
        $mutationName = Submission::gqlMutationNameByContext($form);
        $mutationArguments = SubmissionMutationArguments::getArguments();
        $generatedType = SubmissionGenerator::generateType($form);

        $resolver = Craft::createObject(SubmissionResolver::class);
        $resolver->setResolutionData('form', $form);
        $contentFields = $form->getFields();

        foreach ($contentFields as &$contentField) {
            $contentField->formId = $form->id;
        }

        static::prepareResolver($resolver, $contentFields);

        $mutationArguments = array_merge($mutationArguments, $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY));

        return [
            'name' => $mutationName,
            'description' => 'Save the “' . $form->title . '” submission.',
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'saveSubmission'],
            'type' => $generatedType,
        ];
    }
}
