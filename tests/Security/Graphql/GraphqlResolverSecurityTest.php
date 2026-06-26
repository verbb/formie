<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use verbb\formie\gql\resolvers\HtmlFormResolver;
use verbb\formie\gql\resolvers\ClientFormResolver;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;
use yii\web\NotFoundHttpException;

it('returns a generic not-found message for unknown client form handles', function (): void {
    $missingHandle = 'security-missing-client-' . uniqid();

    try {
        ClientFormResolver::resolveForm(null, [
            'handle' => $missingHandle,
        ]);

        $this->fail('Expected a not-found exception for unknown handle.');
    } catch (NotFoundHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

it('returns a generic not-found message for unknown html form handles', function (): void {
    $missingHandle = 'security-missing-html-' . uniqid();

    try {
        HtmlFormResolver::resolve(null, [
            'handle' => $missingHandle,
        ]);

        $this->fail('Expected a not-found exception for unknown handle.');
    } catch (NotFoundHttpException $exception) {
        expect($exception->getMessage())
            ->toBe('Form not found')
            ->not->toContain($missingHandle);
    }
})->group('security');

it('hides unreadable client form handles behind not-found responses', function (): void {
    $allowedForm = formie()
        ->form(['title' => 'Allowed Client GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();
    $blockedForm = formie()
        ->form(['title' => 'Blocked Client GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $allowedForm->uid . ':read',
    ], function () use ($blockedForm): void {
        expect(fn() => ClientFormResolver::resolveForm(null, [
            'handle' => (string)$blockedForm->handle,
        ]))->toThrow(NotFoundHttpException::class, 'Form not found');
    });
})->group('security');

it('hides unreadable html form handles behind not-found responses', function (): void {
    $allowedForm = formie()
        ->form(['title' => 'Allowed Html GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();
    $blockedForm = formie()
        ->form(['title' => 'Blocked Html GraphQL Form'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $allowedForm->uid . ':read',
    ], function () use ($blockedForm): void {
        expect(fn() => HtmlFormResolver::resolve(null, [
            'handle' => (string)$blockedForm->handle,
        ]))->toThrow(NotFoundHttpException::class, 'Form not found');
    });
})->group('security');

it('requires submission mutation scopes for client graphql session mutations', function (): void {
    $form = formie()
        ->form(['title' => 'Client GraphQL Mutation Permissions'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $form->uid . ':read',
    ], function () use ($form): void {
        expect(fn() => ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => [],
            ],
        ]))->toThrow(Error::class, 'Unable to perform the action.');
    });
})->group('security');

it('requires submission mutation scopes for client graphql submit mutations', function (): void {
    $form = formie()
        ->form(['title' => 'Client GraphQL Submit Permissions'])
        ->singleLineTextField('fullName')
        ->create();

    withGraphqlSchemaScope([
        'formieForms.' . $form->uid . ':read',
    ], function () use ($form): void {
        expect(fn() => ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => [],
                'values' => [
                    'fullName' => 'Security Tester',
                ],
            ],
        ]))->toThrow(Error::class, 'Unable to perform the action.');
    });
})->group('security');

it('requires submission create scopes for save submission by handle mutations', function (): void {
    $form = formie()
        ->form(['title' => 'GraphQL Save Submission Permissions'])
        ->singleLineTextField('fullName')
        ->create();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);

    withGraphqlSchemaScope([
        'formieForms.' . $form->uid . ':read',
    ], function () use ($form, $resolver, $resolveInfo): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'fullName' => 'Security Tester',
            ],
        ], null, $resolveInfo))->toThrow(Error::class, 'Unable to perform the action.');
    });
})->group('security');

it('requires submission delete scopes for delete submission mutations', function (): void {
    $form = formie()
        ->form(['title' => 'GraphQL Delete Submission Permissions'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Delete Me'])
        ->save();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);

    withGraphqlSchemaScope([
        'formieForms.' . $form->uid . ':read',
    ], function () use ($submission, $resolver, $resolveInfo): void {
        expect(fn() => $resolver->deleteSubmission(null, [
            'id' => (int)$submission->id,
        ], null, $resolveInfo))->toThrow(Error::class, 'Unable to perform the action.');
    });
})->group('security');

function withGraphqlSchemaScope(array $scope, callable $callback): void
{
    $gqlService = \Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie GraphQL Resolver Security Test Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }
}
