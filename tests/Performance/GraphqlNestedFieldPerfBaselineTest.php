<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\Gql;
use craft\models\GqlSchema;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Name;
use verbb\formie\fields\Number;
use verbb\formie\fields\SingleLineText;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\queries\FormQuery;
use verbb\formie\gql\queries\SubmissionQuery;
use verbb\formie\gql\types\generators\FormGenerator;
use verbb\formie\gql\types\generators\SubmissionGenerator;

it('captures a graphql schema generation baseline for nested fields', function (string $profileLabel, int $formCount, int $fieldSetCount): void {
    seedGraphqlNestedFieldPerfForms($formCount, $fieldSetCount);

    withGraphqlNestedFieldPerfSchema([
        'formieForms.all:read',
        'formieSubmissions.all:read',
        'formieSubmissions.all:create',
        'formieSubmissions.all:save',
        'formieSubmissions.all:delete',
    ], function () use ($profileLabel, $formCount, $fieldSetCount): void {
        $combined = measureGraphqlNestedFieldPerfPhase(function (): array {
            return [
                'formQueries' => FormQuery::getQueries(false),
                'submissionQueries' => SubmissionQuery::getQueries(false),
                'submissionMutations' => SubmissionMutation::getMutations(),
                'formTypes' => FormGenerator::generateTypes(),
                'submissionTypes' => SubmissionGenerator::generateTypes(),
            ];
        });

        $submissionForms = getSchemaScopedGraphqlNestedFieldForms();
        $drilldown = [
            'contentArguments' => measureGraphqlNestedFieldPerfPhase(fn(): array => SubmissionArguments::getContentArguments())['elapsedMs'],
            'mutationPerForm' => measureGraphqlNestedFieldPerfPhase(function () use ($submissionForms): array {
                $mutations = [];

                foreach ($submissionForms as $form) {
                    $mutations[] = SubmissionMutation::createSaveMutation($form);
                }

                return $mutations;
            })['elapsedMs'],
            'typePerForm' => measureGraphqlNestedFieldPerfPhase(function () use ($submissionForms): array {
                $types = [];

                foreach ($submissionForms as $form) {
                    $types[] = SubmissionGenerator::generateType($form);
                }

                return $types;
            })['elapsedMs'],
            'scopedForms' => count($submissionForms),
        ];

        fwrite(STDOUT, sprintf(
            "GRAPHQL_NESTED_FIELD_PERF %s\n",
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

function seedGraphqlNestedFieldPerfForms(int $formCount, int $fieldSetCount): void
{
    $nameRows = (new Name(['useMultipleFields' => true]))->getSubFields();
    $addressRows = (new Address())->getSubFields();
    $nestedRows = [[
        'fields' => [
            [
                'type' => SingleLineText::class,
                'label' => 'Item Name',
                'handle' => 'itemName',
            ],
            [
                'type' => Number::class,
                'label' => 'Quantity',
                'handle' => 'quantity',
            ],
            [
                'type' => Dropdown::class,
                'label' => 'Status',
                'handle' => 'status',
                'settings' => [
                    'options' => [
                        ['label' => 'Draft', 'value' => 'draft'],
                        ['label' => 'Live', 'value' => 'live'],
                    ],
                ],
            ],
        ],
    ]];

    for ($formIndex = 1; $formIndex <= $formCount; $formIndex++) {
        $builder = formie()->form([
            'title' => "GraphQL Nested Field Perf {$formIndex}",
        ]);

        for ($fieldIndex = 1; $fieldIndex <= $fieldSetCount; $fieldIndex++) {
            $builder
                ->nameField("profileName{$formIndex}_{$fieldIndex}", [
                    'useMultipleFields' => true,
                    'rows' => $nameRows,
                ])
                ->addressField("shippingAddress{$formIndex}_{$fieldIndex}", [
                    'rows' => $addressRows,
                ])
                ->groupField("groupContent{$formIndex}_{$fieldIndex}", [
                    'rows' => $nestedRows,
                ])
                ->repeaterField("lineItems{$formIndex}_{$fieldIndex}", [
                    'rows' => $nestedRows,
                ]);
        }

        $builder->create();
    }
}

function withGraphqlNestedFieldPerfSchema(array $scope, callable $callback): void
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
        'name' => 'Formie GraphQL Nested Field Perf Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
        $gqlService->flushCaches();
    }
}

function getSchemaScopedGraphqlNestedFieldForms(): array
{
    $forms = Formie::$plugin->getForms()->getAllFormsWithLayouts();

    if (Gql::isSchemaAwareOf('formieSubmissions.all')) {
        return $forms;
    }

    return array_values(array_filter($forms, static function($form): bool {
        return Gql::isSchemaAwareOf(Submission::gqlScopesByContext($form));
    }));
}

function measureGraphqlNestedFieldPerfPhase(callable $callback): array
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
