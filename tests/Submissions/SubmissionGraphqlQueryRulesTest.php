<?php

declare(strict_types=1);

use craft\elements\db\ElementQuery;
use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\resolvers\SubmissionResolver;

it('limits submission field query arguments to forms visible in the active graphql schema', function (): void {
    $allowedForm = formie()
        ->form(['title' => 'Allowed Submission Query Args'])
        ->singleLineTextField('allowedField')
        ->create();
    $blockedForm = formie()
        ->form(['title' => 'Blocked Submission Query Args'])
        ->singleLineTextField('blockedField')
        ->create();

    withSubmissionGraphqlScope([
        'formieSubmissions.' . $allowedForm->uid . ':read',
    ], function () use ($allowedForm, $blockedForm): void {
        $arguments = SubmissionArguments::getContentArguments();

        expect($arguments)->toHaveKey('allowedField')
            ->and($arguments)->not->toHaveKey('blockedField');
    });
});

it('requires submission field handle filters to target exactly one form', function (): void {
    $formA = formie()
        ->form(['title' => 'Submission Query Form A'])
        ->singleLineTextField('sharedField')
        ->create();
    $formB = formie()
        ->form(['title' => 'Submission Query Form B'])
        ->singleLineTextField('sharedField')
        ->create();

    withSubmissionGraphqlScope([
        'formieSubmissions.' . $formA->uid . ':read',
        'formieSubmissions.' . $formB->uid . ':read',
    ], function () use ($formA, $formB): void {
        expect(fn() => SubmissionResolver::prepareQuery(null, [
            'form' => [$formA->handle, $formB->handle],
            'sharedField' => 'value',
        ]))->toThrow(Error::class, 'Field handle filters on submission queries require the `form` argument to target exactly one form.');
    });
});

it('requires submission field handle filters to belong to the targeted form', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Query Targeted Form'])
        ->singleLineTextField('allowedField')
        ->create();

    withSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':read',
    ], function () use ($form): void {
        expect(fn() => SubmissionResolver::prepareQuery(null, [
            'form' => $form->handle,
            'missingField' => 'value',
        ]))->toThrow(Error::class, 'Field handle filters on submission queries must belong to the targeted form.');
    });
});

it('allows submission field handle filters for a single targeted form', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Query Single Form'])
        ->singleLineTextField('allowedField')
        ->create();

    withSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':read',
    ], function () use ($form): void {
        $query = SubmissionResolver::prepareQuery(null, [
            'form' => $form->handle,
            'allowedField' => 'value',
        ]);

        expect($query)->toBeInstanceOf(ElementQuery::class);
    });
});

function withSubmissionGraphqlScope(array $scope, callable $callback): void
{
    $gqlService = \Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie Submission Query Rule Test Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }
}
