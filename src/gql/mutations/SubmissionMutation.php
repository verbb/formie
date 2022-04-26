<?php
namespace verbb\formie\gql\mutations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\mutations\SubmissionArguments as SubmissionMutationArguments;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
use verbb\formie\gql\types\generators\SubmissionGenerator;
use verbb\formie\gql\types\input\CaptchaInputType;

use Craft;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\helpers\Gql;

use yii\base\InvalidConfigException;

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
            $scope = 'formieSubmissions.' . $form->uid;

            $canCreateAll = Gql::canSchema('formieSubmissions.all', 'create');
            $canSaveAll = Gql::canSchema('formieSubmissions.all', 'save');
            $canDeleteAll = Gql::canSchema('formieSubmissions.all', 'delete');

            $canCreate = Gql::canSchema($scope, 'create');
            $canSave = Gql::canSchema($scope, 'save');
            $canDelete = Gql::canSchema($scope, 'delete');

            if ($canCreateAll || $canSaveAll || $canCreate || $canSave) {
                $mutation = static::createSaveMutation($form);
                $mutationList[$mutation['name']] = $mutation;
            }

            if (!$createDeleteMutation && ($canDeleteAll || $canDelete)) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteSubmission'] = [
                'name' => 'deleteSubmission',
                'args' => [
                    'id' => Type::nonNull(Type::int()),
                    'siteId' => Type::nonNull(Type::int()),
                ],
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
     * @throws InvalidConfigException
     */
    public static function createSaveMutation(Form $form): array
    {
        $mutationName = Submission::gqlMutationNameByContext($form);
        $mutationArguments = SubmissionMutationArguments::getArguments();
        $generatedType = SubmissionGenerator::generateType($form);

        $resolver = Craft::createObject(SubmissionResolver::class);
        $resolver->setResolutionData('form', $form);
        $contentFields = $form->getCustomFields();

        foreach ($contentFields as $contentField) {
            $contentField->formId = $form->id;
        }

        static::prepareResolver($resolver, $contentFields);

        $captchaArguments = [];

        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($form);

        // Add in any enabled captchas for the form, so they can be allowed to send tokens
        foreach ($captchas as $captcha) {
            $handle = $captcha->getGqlHandle();

            $captchaArguments[$handle] = [
                'name' => $handle,
                'type' => CaptchaInputType::getType(),
            ];
        }

        $mutationArguments = array_merge($mutationArguments, $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY), $captchaArguments);

        return [
            'name' => $mutationName,
            'description' => 'Save the “' . $form->title . '” submission.',
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'saveSubmission'],
            'type' => $generatedType,
        ];
    }
}
