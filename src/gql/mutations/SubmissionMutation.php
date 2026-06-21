<?php
namespace verbb\formie\gql\mutations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\mutations\SubmissionArguments as SubmissionMutationArguments;
use verbb\formie\gql\interfaces\SubmissionInterface;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
use verbb\formie\gql\types\ArrayType;
use verbb\formie\gql\types\generators\SubmissionGenerator;

use Craft;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\helpers\Gql;

use GraphQL\Type\Definition\Type;

class SubmissionMutation extends Mutation
{
    // Static Methods
    // =========================================================================

    public static function getMutations(): array
    {
        $mutationList = [];
        $createDeleteMutation = false;
        $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();
        $canCreateAll = Gql::canSchema('formieSubmissions.all', 'create');
        $canSaveAll = Gql::canSchema('formieSubmissions.all', 'save');
        $canDeleteAll = Gql::canSchema('formieSubmissions.all', 'delete');
        $registerSaveSubmissionMutation = $canCreateAll || $canSaveAll;

        foreach ($forms as $form) {
            $scope = 'formieSubmissions.' . $form->uid;

            $canCreate = $canCreateAll || Gql::canSchema($scope, 'create');
            $canSave = $canSaveAll || Gql::canSchema($scope, 'save');
            $canDelete = $canDeleteAll || Gql::canSchema($scope, 'delete');

            if ($canCreateAll || $canSaveAll || $canCreate || $canSave) {
                $mutation = static::createSaveMutation($form);
                $mutationList[$mutation['name']] = $mutation;

                if (!$registerSaveSubmissionMutation) {
                    $registerSaveSubmissionMutation = true;
                }
            }

            if (!$createDeleteMutation && ($canDeleteAll || $canDelete)) {
                $createDeleteMutation = true;
            }
        }

        if ($registerSaveSubmissionMutation) {
            $mutationList['saveSubmission'] = static::createGenericSaveMutation();
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

    public static function createSaveMutation(Form $form): array
    {
        $mutationName = Submission::gqlMutationNameByContext($form);
        $mutationArguments = SubmissionMutationArguments::getArguments();
        $generatedType = SubmissionGenerator::generateType($form);
        $resolver = static::createConfiguredResolver($form);

        $captchaArguments = Formie::$plugin->getIntegrations()->getGqlCaptchaArgumentsForForm($form);
        $mutationArguments = array_merge(
            $mutationArguments,
            $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY),
            $captchaArguments,
        );

        return [
            'name' => $mutationName,
            'description' => 'Save the “' . $form->title . '” submission.',
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'saveSubmission'],
            'type' => $generatedType,
        ];
    }

    public static function createGenericSaveMutation(): array
    {
        return [
            'name' => 'saveSubmission',
            'description' => 'Save a submission for the form identified by `formHandle`.',
            'args' => array_merge(SubmissionMutationArguments::getArguments(), [
                'formHandle' => [
                    'name' => 'formHandle',
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The form handle.',
                ],
                'fields' => [
                    'name' => 'fields',
                    'type' => ArrayType::getType(),
                    'description' => 'Field values keyed by field handle.',
                ],
                'captchas' => [
                    'name' => 'captchas',
                    'type' => ArrayType::getType(),
                    'description' => 'Captcha payloads keyed by captcha GraphQL handle.',
                ],
            ]),
            'resolve' => [Craft::createObject(SubmissionResolver::class), 'saveSubmissionByHandle'],
            'type' => SubmissionInterface::getType(),
        ];
    }

    public static function createConfiguredResolver(Form $form): SubmissionResolver
    {
        $resolver = Craft::createObject(SubmissionResolver::class);
        $resolver->setResolutionData('form', $form);
        $contentFieldConfigs = Formie::$plugin->getFields()->getAllFieldConfigsForForms([(int)$form->id])[(int)$form->id] ?? [];

        static::prepareFormieResolver($resolver, $contentFieldConfigs);

        return $resolver;
    }

    private static function prepareFormieResolver(SubmissionResolver $resolver, array $contentFieldConfigs): void
    {
        $fieldList = [];
        $fieldsService = Formie::$plugin->getFields();

        // Keep schema generation on normalized field config rows for simple first-party fields.
        // Only fall back to hydrated field objects when a field type has not opted into the
        // config-driven GQL contract yet, so client-only field logic remains the compatibility net.
        foreach ($contentFieldConfigs as $contentFieldConfig) {
            $contentFieldType = $fieldsService->getFieldConfigContentGqlMutationArgumentType($contentFieldConfig);
            $handle = $contentFieldConfig['handle'] ?? null;

            if (!$handle) {
                continue;
            }

            $fieldList[$handle] = $contentFieldType;
            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $resolver->setValueNormalizer($handle, $configArray['normalizeValue']);
            }
        }

        $resolver->setResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY, $fieldList);
    }
}
