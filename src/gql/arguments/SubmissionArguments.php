<?php
namespace verbb\formie\gql\arguments;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\gql\base\ElementArguments;
use craft\helpers\Gql as GqlHelper;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(self::getStaticArguments(), self::getContentArguments());
    }

    public static function getStaticArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'form' => [
                'name' => 'form',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the submission’s form handle.',
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the submission’s status.',
            ],
            'statusId' => [
                'name' => 'statusId',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the submission’s status ID.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the submission’s site ID.',
            ],
            'isIncomplete' => [
                'name' => 'isIncomplete',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on the submission’s incomplete state.',
            ],
            'isSpam' => [
                'name' => 'isSpam',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on the submission’s spam state.',
            ],
        ]);
    }

    public static function getStaticArgumentKeys(): array
    {
        return array_keys(self::getStaticArguments());
    }

    public static function getContentArguments(): array
    {
        $arguments = [];
        $fieldsService = Formie::$plugin->getFields();
        $forms = self::_getSchemaScopedForms();
        $fieldConfigsByForm = $fieldsService->getAllFieldConfigsForForms(array_map(static fn($form): int => (int)$form->id, $forms));

        foreach ($forms as $form) {
            foreach ($fieldConfigsByForm[$form->id] ?? [] as $fieldConfig) {
                $handle = $fieldConfig['handle'] ?? null;

                if ($handle && !isset($arguments[$handle])) {
                    $arguments[$handle] = $fieldsService->getFieldConfigContentGqlQueryArgumentType($fieldConfig);
                }
            }
        }

        return array_merge(parent::getContentArguments(), $arguments);
    }

    public static function getDynamicFieldArgumentHandlesFor(array $arguments): array
    {
        return array_values(array_diff(array_keys($arguments), self::getStaticArgumentKeys()));
    }

    public static function getSingleTargetFormHandle(mixed $formArgument): ?string
    {
        $handles = self::extractTargetFormHandles($formArgument);

        return count($handles) === 1 ? $handles[0] : null;
    }

    public static function extractTargetFormHandles(mixed $formArgument): array
    {
        if (is_string($formArgument)) {
            $formArgument = [$formArgument];
        }

        if (!is_array($formArgument)) {
            return [];
        }

        $handles = array_values(array_unique(array_filter(array_map(static function(mixed $value): string {
            return is_string($value) ? trim($value) : '';
        }, $formArgument))));

        return array_values(array_filter($handles));
    }


    // Private Methods
    // =========================================================================

    private static function _getSchemaScopedForms(): array
    {
        $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();

        if (GqlHelper::isSchemaAwareOf('formieSubmissions.all')) {
            return $forms;
        }

        return array_values(array_filter($forms, static function(Form $form): bool {
            return GqlHelper::isSchemaAwareOf(Submission::gqlScopesByContext($form));
        }));
    }
}
