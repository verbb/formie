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

it('captures a graphql schema generation baseline for synthetic form volume', function (string $profileLabel, int $formCount, int $fieldCount, int $maxElapsedMs): void {
    seedGraphqlPerfForms($formCount, $fieldCount);

    withGraphqlPerfSchema([
        'formieForms.all:read',
        'formieSubmissions.all:read',
        'formieSubmissions.all:create',
        'formieSubmissions.all:save',
        'formieSubmissions.all:delete',
    ], function () use ($profileLabel, $formCount, $fieldCount, $maxElapsedMs): void {
        $combined = measureGraphqlPerfPhase(function (): array {
            return [
                'formQueries' => FormQuery::getQueries(false),
                'submissionQueries' => SubmissionQuery::getQueries(false),
                'submissionMutations' => SubmissionMutation::getMutations(),
                'formTypes' => FormGenerator::generateTypes(),
                'submissionTypes' => SubmissionGenerator::generateTypes(),
            ];
        });

        $phaseBreakdown = [
            'formQueries' => measureGraphqlPerfPhase(fn(): array => FormQuery::getQueries(false))['elapsedMs'],
            'submissionQueries' => measureGraphqlPerfPhase(fn(): array => SubmissionQuery::getQueries(false))['elapsedMs'],
            'submissionMutations' => measureGraphqlPerfPhase(fn(): array => SubmissionMutation::getMutations())['elapsedMs'],
            'formTypes' => measureGraphqlPerfPhase(fn(): array => FormGenerator::generateTypes())['elapsedMs'],
            'submissionTypes' => measureGraphqlPerfPhase(fn(): array => SubmissionGenerator::generateTypes())['elapsedMs'],
        ];

        $submissionForms = getSchemaScopedSubmissionForms();
        $submissionDrilldown = [
            'staticArguments' => measureGraphqlPerfPhase(fn(): array => SubmissionArguments::getStaticArguments())['elapsedMs'],
            'contentArguments' => measureGraphqlPerfPhase(fn(): array => SubmissionArguments::getContentArguments())['elapsedMs'],
            'mutationPerForm' => measureGraphqlPerfPhase(function () use ($submissionForms): array {
                $mutations = [];

                foreach ($submissionForms as $form) {
                    $mutations[] = SubmissionMutation::createSaveMutation($form);
                }

                return $mutations;
            })['elapsedMs'],
            'typePerForm' => measureGraphqlPerfPhase(function () use ($submissionForms): array {
                $types = [];

                foreach ($submissionForms as $form) {
                    $types[] = SubmissionGenerator::generateType($form);
                }

                return $types;
            })['elapsedMs'],
            'scopedForms' => count($submissionForms),
        ];

        emitGraphqlPerfBreakdown($profileLabel, $formCount, $fieldCount, [
            'combinedMs' => $combined['elapsedMs'],
            'phaseBreakdownMs' => $phaseBreakdown,
            'submissionDrilldownMs' => $submissionDrilldown,
            'counts' => [
                'formQueries' => count($combined['result']['formQueries']),
                'submissionQueries' => count($combined['result']['submissionQueries']),
                'submissionMutations' => count($combined['result']['submissionMutations']),
                'formTypes' => count($combined['result']['formTypes']),
                'submissionTypes' => count($combined['result']['submissionTypes']),
            ],
        ]);

        expect($combined['result']['formQueries'])->toHaveCount(3)
            ->and($combined['result']['submissionQueries'])->toHaveCount(3)
            ->and(count($combined['result']['submissionMutations']))->toBeGreaterThanOrEqual($formCount + 1)
            ->and(count($combined['result']['formTypes']))->toBeGreaterThanOrEqual($formCount)
            ->and(count($combined['result']['submissionTypes']))->toBeGreaterThanOrEqual($formCount)
            // Soft guardrail: fail only on obvious regressions.
            ->and($combined['elapsedMs'])->toBeLessThan($maxElapsedMs);
    });
})->with([
    'small' => ['small', 5, 10, 20000],
    'medium' => ['medium', 15, 15, 35000],
    'large' => ['large', 30, 15, 60000],
])->group('perf');

function seedGraphqlPerfForms(int $formCount, int $fieldCount): void
{
    for ($formIndex = 1; $formIndex <= $formCount; $formIndex++) {
        $builder = formie()->form([
            'title' => "GraphQL Perf Form {$formIndex}",
        ]);

        for ($fieldIndex = 1; $fieldIndex <= $fieldCount; $fieldIndex++) {
            $builder->singleLineTextField("field{$formIndex}_{$fieldIndex}");
        }

        $builder->create();
    }
}

function withGraphqlPerfSchema(array $scope, callable $callback): void
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
        'name' => 'Formie GraphQL Perf Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
        $gqlService->flushCaches();
    }
}

function getSchemaScopedSubmissionForms(): array
{
    $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();

    if (Gql::isSchemaAwareOf('formieSubmissions.all')) {
        return $forms;
    }

    return array_values(array_filter($forms, static function($form): bool {
        return Gql::isSchemaAwareOf(Submission::gqlScopesByContext($form));
    }));
}

function measureGraphqlPerfPhase(callable $callback): array
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

function emitGraphqlPerfBreakdown(string $profileLabel, int $formCount, int $fieldCount, array $metrics): void
{
    fwrite(STDOUT, sprintf(
        "GRAPHQL_SCHEMA_PERF %s\n",
        json_encode([
            'profile' => $profileLabel,
            'forms' => $formCount,
            'fieldsPerForm' => $fieldCount,
            'combinedMs' => $metrics['combinedMs'],
            'phaseBreakdownMs' => $metrics['phaseBreakdownMs'],
            'submissionDrilldownMs' => $metrics['submissionDrilldownMs'],
            'counts' => $metrics['counts'],
        ], JSON_UNESCAPED_SLASHES)
    ));
}
