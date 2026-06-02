<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\Gql;
use craft\models\GqlSchema;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\gql\types\generators\FormGenerator;
use verbb\formie\gql\types\generators\SubmissionGenerator;

it('captures a graphql schema generation baseline for remaining complex form fields', function (string $profileLabel, int $formCount, int $fieldSetCount): void {
    seedGraphqlComplexFieldPerfForms($formCount, $fieldSetCount);

    withGraphqlComplexFieldPerfSchema([
        'formieForms.all:read',
        'formieSubmissions.all:read',
        'formieSubmissions.all:create',
        'formieSubmissions.all:save',
        'formieSubmissions.all:delete',
    ], function () use ($profileLabel, $formCount, $fieldSetCount): void {
        $combined = measureGraphqlComplexFieldPerfPhase(function (): array {
            return [
                'formQueries' => FormQuery::getQueries(false),
                'submissionQueries' => SubmissionQuery::getQueries(false),
                'submissionMutations' => SubmissionMutation::getMutations(),
                'formTypes' => FormGenerator::generateTypes(),
                'submissionTypes' => SubmissionGenerator::generateTypes(),
            ];
        });

        $submissionForms = getSchemaScopedGraphqlComplexFieldForms();
        $drilldown = [
            'contentArguments' => measureGraphqlComplexFieldPerfPhase(fn(): array => SubmissionArguments::getContentArguments())['elapsedMs'],
            'mutationPerForm' => measureGraphqlComplexFieldPerfPhase(function () use ($submissionForms): array {
                $mutations = [];

                foreach ($submissionForms as $form) {
                    $mutations[] = SubmissionMutation::createSaveMutation($form);
                }

                return $mutations;
            })['elapsedMs'],
            'typePerForm' => measureGraphqlComplexFieldPerfPhase(function () use ($submissionForms): array {
                $types = [];

                foreach ($submissionForms as $form) {
                    $types[] = SubmissionGenerator::generateType($form);
                }

                return $types;
            })['elapsedMs'],
            'scopedForms' => count($submissionForms),
        ];

        fwrite(STDOUT, sprintf(
            "GRAPHQL_COMPLEX_FIELD_PERF %s\n",
            json_encode([
                'profile' => $profileLabel,
                'forms' => $formCount,
                'fieldSetsPerForm' => $fieldSetCount,
                'combinedMs' => $combined['elapsedMs'],
                'drilldownMs' => $drilldown,
                'counts' => [
                    'submissionMutations' => count($combined['result']['submissionMutations']),
                    'formTypes' => count($combined['result']['formTypes']),
                    'submissionTypes' => count($combined['result']['submissionTypes']),
                ],
            ], JSON_UNESCAPED_SLASHES)
        ));

        expect(count($combined['result']['submissionMutations']))->toBeGreaterThanOrEqual($formCount)
            ->and(count($combined['result']['formTypes']))->toBeGreaterThanOrEqual($formCount)
            ->and(count($combined['result']['submissionTypes']))->toBeGreaterThanOrEqual($formCount);
    });
})->with([
    'small' => ['small', 5, 2],
    'medium' => ['medium', 15, 3],
    'large' => ['large', 30, 3],
])->group('perf');

function seedGraphqlComplexFieldPerfForms(int $formCount, int $fieldSetCount): void
{
    $recipientOptions = [
        ['label' => 'Sales', 'value' => 'sales@example.test'],
        ['label' => 'Support', 'value' => 'support@example.test'],
    ];

    $tableColumns = [
        'textCol' => ['heading' => 'Text', 'handle' => 'textCol', 'type' => 'singleline'],
        'numberCol' => ['heading' => 'Number', 'handle' => 'numberCol', 'type' => 'number'],
        'dateCol' => ['heading' => 'Date', 'handle' => 'dateCol', 'type' => 'date'],
        'toggleCol' => ['heading' => 'Toggle', 'handle' => 'toggleCol', 'type' => 'lightswitch'],
    ];

    for ($formIndex = 1; $formIndex <= $formCount; $formIndex++) {
        $builder = formie()->form([
            'title' => "GraphQL Complex Field Perf {$formIndex}",
        ]);

        for ($fieldIndex = 1; $fieldIndex <= $fieldSetCount; $fieldIndex++) {
            $builder
                ->fileUploadField("attachments{$formIndex}_{$fieldIndex}", ['restrictFiles' => false])
                ->tableField("lineItems{$formIndex}_{$fieldIndex}", ['columns' => $tableColumns])
                ->recipientsField("recipients{$formIndex}_{$fieldIndex}", [
                    'displayType' => 'checkboxes',
                    'options' => $recipientOptions,
                ]);
        }

        $builder->create();
    }
}

function withGraphqlComplexFieldPerfSchema(array $scope, callable $callback): void
{
    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->flushCaches();
    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie GraphQL Complex Field Perf Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
        $gqlService->flushCaches();
    }
}

function getSchemaScopedGraphqlComplexFieldForms(): array
{
    $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();

    if (Gql::isSchemaAwareOf('formieSubmissions.all')) {
        return $forms;
    }

    return array_values(array_filter($forms, static function($form): bool {
        return Gql::isSchemaAwareOf(Submission::gqlScopesByContext($form));
    }));
}

function measureGraphqlComplexFieldPerfPhase(callable $callback): array
{
    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal outside scoped test setup.
    }

    $gqlService->flushCaches();

    if ($activeSchema) {
        $gqlService->setActiveSchema($activeSchema);
    }

    Formie::$plugin->getForms()->invalidateFormCaches();

    $started = microtime(true);
    $result = $callback();

    return [
        'result' => $result,
        'elapsedMs' => (int)((microtime(true) - $started) * 1000),
    ];
}
