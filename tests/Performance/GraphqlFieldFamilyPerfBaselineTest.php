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

it('captures a graphql schema generation baseline for mixed field families', function (string $profileLabel, int $formCount, int $fieldSetCount): void {
    seedGraphqlFieldFamilyPerfForms($formCount, $fieldSetCount);

    withGraphqlFieldFamilyPerfSchema([
        'formieForms.all:read',
        'formieSubmissions.all:read',
        'formieSubmissions.all:create',
        'formieSubmissions.all:save',
        'formieSubmissions.all:delete',
    ], function () use ($profileLabel, $formCount, $fieldSetCount): void {
        $combined = measureGraphqlFieldFamilyPerfPhase(function (): array {
            return [
                'formQueries' => FormQuery::getQueries(false),
                'submissionQueries' => SubmissionQuery::getQueries(false),
                'submissionMutations' => SubmissionMutation::getMutations(),
                'formTypes' => FormGenerator::generateTypes(),
                'submissionTypes' => SubmissionGenerator::generateTypes(),
            ];
        });

        $submissionForms = getSchemaScopedGraphqlFieldFamilyForms();
        $drilldown = [
            'contentArguments' => measureGraphqlFieldFamilyPerfPhase(fn(): array => SubmissionArguments::getContentArguments())['elapsedMs'],
            'mutationPerForm' => measureGraphqlFieldFamilyPerfPhase(function () use ($submissionForms): array {
                $mutations = [];

                foreach ($submissionForms as $form) {
                    $mutations[] = SubmissionMutation::createSaveMutation($form);
                }

                return $mutations;
            })['elapsedMs'],
            'typePerForm' => measureGraphqlFieldFamilyPerfPhase(function () use ($submissionForms): array {
                $types = [];

                foreach ($submissionForms as $form) {
                    $types[] = SubmissionGenerator::generateType($form);
                }

                return $types;
            })['elapsedMs'],
            'scopedForms' => count($submissionForms),
        ];

        fwrite(STDOUT, sprintf(
            "GRAPHQL_FIELD_FAMILY_PERF %s\n",
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
    'small' => ['small', 5, 3],
    'medium' => ['medium', 15, 4],
    'large' => ['large', 30, 4],
])->group('perf');

function seedGraphqlFieldFamilyPerfForms(int $formCount, int $fieldSetCount): void
{
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
        ['label' => 'Three', 'value' => 'three'],
        ['label' => 'Four', 'value' => 'four'],
    ];

    for ($formIndex = 1; $formIndex <= $formCount; $formIndex++) {
        $builder = formie()->form([
            'title' => "GraphQL Field Family Perf {$formIndex}",
        ]);

        for ($fieldIndex = 1; $fieldIndex <= $fieldSetCount; $fieldIndex++) {
            // These field families are the next config-first targets after the scalar text/number
            // pass, so this baseline keeps the measurement aligned with the code we are widening.
            $builder
                ->dropdownField("dropdown{$formIndex}_{$fieldIndex}", ['options' => $options])
                ->radioField("radio{$formIndex}_{$fieldIndex}", ['options' => $options])
                ->checkboxesField("checkboxes{$formIndex}_{$fieldIndex}", ['options' => $options])
                ->dateField("date{$formIndex}_{$fieldIndex}");
        }

        $builder->create();
    }
}

function withGraphqlFieldFamilyPerfSchema(array $scope, callable $callback): void
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
        'name' => 'Formie GraphQL Field Family Perf Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
        $gqlService->flushCaches();
    }
}

function getSchemaScopedGraphqlFieldFamilyForms(): array
{
    $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();

    if (Gql::isSchemaAwareOf('formieSubmissions.all')) {
        return $forms;
    }

    return array_values(array_filter($forms, static function($form): bool {
        return Gql::isSchemaAwareOf(Submission::gqlScopesByContext($form));
    }));
}

function measureGraphqlFieldFamilyPerfPhase(callable $callback): array
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
